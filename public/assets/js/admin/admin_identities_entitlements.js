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
        {type:"switch", label: "Manually Override This Entitlement",name: "override",value:false,options:[{value:false,label:'Use Defaults (No Manual Override)'},{value:true,label:'Manual Override'}],help:'If "Manual Override" is not selected, this entitlement may be updated or deleted by this identity\'s calculated entitlements!'},
        {name:"override_expiration",required:true, "label":"Manual Override Expiration",type:"date", show:[{type:'matches',name:'override',value:true}],format:{input: "YYYY-MM-DD"},help:'This manual override will be enforced until the date specified, at which point it will be updated or deleted by this user\'s calculated entitlements'},
        {name:"override_description", required:true, "label":"Manual Override Description",type:"textarea", show:[{type:'matches',name:'override',value:true}],limit:100},
        {type:"text", name:"override_identity_id", label:"Override Identity", show:false, parse:false,template:"{{attributes.override_identity.first_name}} {{attributes.override_identity.last_name}}"},    
    ], data: data
    }).on("add",function(grid_event) {
        new gform({
            "legend":"Add Manual Override Entitlement",
            "name": "override_entitlement",
            "fields": [
                {name:"entitlement_id","label":"Entitlement",type:"select",options:"/api/entitlements?limit=override_add",format:{label:"{{name}}", value:"{{id}}"},edit:true},
                {name:"type","label":"Type",type:"select",options:[{label:'Add',value:'add'},{label:'Remove',value:'remove'}]},
                {type:"checkbox", name: "override",value:true,options:[{value:false,},{value:true}],show:false,parse:true},
                {name:"override_description", required:true, "label":"Manual Override Description",type:"textarea",limit:100},
                {name:"override_expiration",required:true, "label":"Manual Override Expiration",type:"date", show:[{type:'matches',name:'override',value:true}],format:{input: "YYYY-MM-DD"},help:'This manual override will be enforced until the date specified, at which point it will be updated or deleted by this user\'s calculated entitlements'},
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

