ajax.get('/api/identities/'+id+'/entitlements',function(data) {
    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
    item_template: gform.stencils['table_row'],
    search: false,columns: false,upload:false,download:false,title:'Entitlements',
    entries:[],
    actions:[
        {"name":"add","label":"Add Entitlement"},
        {"name":"edit","label":"Update Identity Entitlement"},
    ],
    count:20,
    schema:[
        {type:"hidden", name:"id"},
        {name:"entitlement_id","label":"Entitlement",type:"select",options:"/api/entitlements",format:{label:"{{name}}", value:"{{id}}"},edit:false},
        {name:"type","label":"Type",type:"select",options:[{label:'Add',value:'add'},{label:'Remove',value:'remove'}]},
        {type:"switch", label: "Reason",name: "override",value:false,options:[{value:false,label:'Automatic Entitlement'},{value:true,label:'Manual Entitlement'}],help:'If "Manual Entitlement" is not selected, this entitlement may be updated or deleted by this identity\'s calculated entitlements!'},
        {type:"switch", label: "Expire?",name: "expire",value:false,options:[{value:false,label:'No Expiration'},{value:true,label:'Set Expiration Date'}],show:[{type:'matches',name:'override',value:true}]},
        {name:"expiration_date",required:true, "label":"Expiration Date",type:"date", show:[{type:'matches',name:'override',value:true},{type:'matches',name:'expire',value:true}],format:{input: "YYYY-MM-DD"},help:'This manual override will be enforced until the date specified, at which point it will be updated or deleted by this user\'s calculated entitlements'},
        {name:"description", required:true, "label":"Description",type:"textarea", show:[{type:'matches',name:'override',value:true}],limit:512},
        {type:"user", name:"sponsor_id", label:"Sponsor", show:true, parse:true,show:[{type:'matches',name:'override',value:true},{type:'matches',name:'type',value:'add'}],template:"{{attributes.sponsor.first_name}} {{attributes.sponsor.last_name}}",required:'show'},    
        {type:"switch", label: "Renew?",name: "sponsor_renew_allow",value:false,options:[{value:false,label:'No Renew'},{value:true,label:'Allow Renew'},{type:'matches',name:'type',value:'add'}],show:[{type:'matches',name:'override',value:true},{type:'matches',name:'expire',value:true}]},
        {type:"text", name:"sponsor_renew_days", label:"Renewal Days", show:true, parse:true,show:[{type:'matches',name:'sponsor_renew_allow',value:true},{type:'matches',name:'override',value:true},{type:'matches',name:'type',value:'add'},{type:'matches',name:'expire',value:true}],required:'show'},    
        {type:"user", name:"override_identity_id", label:"Updated By", show:true, parse:true,show:[{type:'matches',name:'override',value:true}],template:"{{attributes.override_identity.first_name}} {{attributes.override_identity.last_name}}",show:false},    
    ], data: data
    }).on("add",function(grid_event) {
        new gform({
            "legend":"Add Manual Override Entitlement",
            "name": "override_entitlement",
            "fields": [
                {name:"entitlement_id","label":"Entitlement",type:"select",options:"/api/entitlements",format:{label:"{{name}}", value:"{{id}}"},edit:true},
                {name:"type","label":"Type",type:"select",options:[{label:'Add',value:'add'},{label:'Remove',value:'remove'}]},
                {type:"switch", label: "Reason",name: "override",value:true,show:false,parse:true},
                {type:"switch", label: "Expire?",name: "expire",value:false,options:[{value:false,label:'No Expiration'},{value:true,label:'Set Expiration Date'}],show:[{type:'matches',name:'override',value:true}]},
                {name:"expiration_date",required:true, "label":"Expiration Date",type:"date", show:[{type:'matches',name:'override',value:true},{type:'matches',name:'expire',value:true}],format:{input: "YYYY-MM-DD"},help:'This manual override will be enforced until the date specified, at which point it will be updated or deleted by this user\'s calculated entitlements'},
                {name:"description", required:true, "label":"Description",type:"textarea", show:[{type:'matches',name:'override',value:true}],limit:512},
                {type:"user", name:"sponsor_id", label:"Sponsor", show:true, parse:true,show:[{type:'matches',name:'override',value:true},{type:'matches',name:'type',value:'add'}],template:"{{attributes.sponsor.first_name}} {{attributes.sponsor.last_name}}",required:'show'},    
                {type:"switch", label: "Renew?",name: "sponsor_renew_allow",value:false,options:[{value:false,label:'No Renew'},{value:true,label:'Allow Renew'}],show:[{type:'matches',name:'override',value:true},{type:'matches',name:'type',value:'add'},{type:'matches',name:'expire',value:true}]},
                {type:"text", name:"sponsor_renew_days", label:"Renewal Days", show:true, parse:true,show:[{type:'matches',name:'sponsor_renew_allow',value:true},{type:'matches',name:'override',value:true},{type:'matches',name:'type',value:'add'},{type:'matches',name:'expire',value:true}],required:'show'},    
                {type:"user", name:"override_identity_id", label:"Updated By", show:true, parse:true,show:[{type:'matches',name:'override',value:true}],template:"{{attributes.override_identity.first_name}} {{attributes.override_identity.last_name}}",show:false},    
            ]
        }).on('save',function(form_event){
            toastr.info('Processing... Please Wait')
            form_event.form.trigger('close');
            ajax.post('/api/identities/'+id+'/entitlements',form_event.form.get(),function(data) {
                gdg.add(data);
            },function(data){
                // Do nothing?
            });
        }).on('cancel',function(form_event){
            form_event.form.trigger('close');
        }).modal()
    }).on("model:edited",function(grid_event) {
        ajax.put('/api/identities/'+id+'/entitlements/'+grid_event.model.attributes.id,grid_event.model.attributes,function(data) {
            grid_event.model.update(data)
        },function(data) {
            grid_event.model.undo();
        });
    });
});

