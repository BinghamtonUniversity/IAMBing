ajax.get('/api/identities/'+id+'/accounts',function(data) {
    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
    item_template: gform.stencils['table_row'],
    search: false,columns: false,upload:false,download:false,title:'Identities',
    entries:[],
    actions:[
        {"name":"add","label":"Create Account"},
        {"name":"edit","label":"Change Account Status"},
        '','',
        {"name":"delete","label":"Delete Account"},
    ],
    count:20,
    schema:[
        {type:"hidden", name:"id"},
        {name:"system_id","label":"System",type:"select",options:"/api/systems",format:{label:"{{name}}", value:"{{id}}"}},
        {type:"text", name:"account_id", label:"Account ID", show:false},
        {
            "type": "select",
            "label": "Status",
            "name": "status",
            "options": [
                {
                    "label": "Active",
                    "value": "active"
                },
                {
                    "label": "Disabled",
                    "value": "disabled"
                }
            ],
        },
        {type:"switch", label: "Manually Override This Account",name: "override",value:false,options:[{value:false,label:'Use Defaults (No Manual Override)'},{value:true,label:'Manual Override'}],help:'If "Manual Override" is not selected, this account may be updated or deleted by this identity\'s calculated entitlements!'},
        {name:"override_expiration",required:true, "label":"Manual Override Expiration",type:"date", show:[{type:'matches',name:'override',value:true}],format:{input: "YYYY-MM-DD"},help:'This manual override will be enforced until the date specified, at which point it will be updated or deleted by this user\'s calculated entitlements'},
        {name:"override_description", required:true, "label":"Manual Override Description",type:"textarea", show:[{type:'matches',name:'override',value:true}],limit:100},
        {type:"text", name:"override_identity_id", label:"Override Identity", show:false, parse:false,template:"{{attributes.override_identity.first_name}} {{attributes.override_identity.last_name}}"},    
    ], data: data
    }).on("add",function(grid_event) {
        new gform({
            "legend":"Add Manual Override Account",
            "name": "override_account",
            "fields": [
                {type:"hidden", name:"id"},
                {name:"system_id","label":"System",type:"select",options:"/api/systems",format:{label:"{{name}}", value:"{{id}}"}},
                {type:"switch", label: "Use custom account ID",name: "use_default_account_id",value:false,options:[
                    {value:false,label:'Use Defaults (No Manual Override)'},
                        {value:true,label:'Manual Override'}]},
                {type:"text", name:"account_id", label:"Account ID", show:[
                    {type:'matches',name:'use_default_account_id',value:false}], required:'show',help:'This is the unique identifier which will be used for this account.  It may be a username or some other unique id.'},    
                {
                    "type": "select",
                    "label": "Status",
                    "name": "status",
                    "options": [
                        {
                            "label": "Active",
                            "value": "active"
                        },
                        {
                            "label": "Disabled",
                            "value": "disabled"
                        }
                    ],
                },
                {type:"switch", label: "Manually Override This Account",name: "override",value:false,options:[{value:false,label:'Use Defaults (No Manual Override)'},{value:true,label:'Manual Override'}],help:'If "Manual Override" is not selected, this account may be updated or deleted by this identity\'s calculated entitlements!'},
                {name:"override_description", required:true, "label":"Manual Override Description",type:"textarea", show:[{type:'matches',name:'override',value:true}],limit:100},
                {type:"text", name:"override_identity_id", label:"Override Identity", show:false, parse:false,template:"{{attributes.override_identity.first_name}} {{attributes.override_identity.last_name}}"},

            ]
        }).on('save',function(form_event){
            toastr.info('Processing... Please Wait')
            form_event.form.trigger('close');
            ajax.post('/api/identities/'+id+'/accounts',form_event.form.get(),function(data) {
                gdg.add(data);
            },function(data){
                // Do nothing?
            });
        }).on('cancel',function(form_event){
            form_event.form.trigger('close');
        }).modal()
    }).on("model:edited",function(grid_event) {
        ajax.put('/api/identities/'+id+'/accounts/'+grid_event.model.attributes.id,grid_event.model.attributes,function(data) {
            grid_event.model.update(data)
        },function(data) {
            grid_event.model.undo();
        }); 
    }).on("model:deleted",function(grid_event) {
        ajax.delete('/api/identities/'+id+'/accounts/'+grid_event.model.attributes.id,{},
            function(data) {},
            function(data) {
            grid_event.model.undo();
        });
    });
});

