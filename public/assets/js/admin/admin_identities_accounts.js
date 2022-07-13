ajax.get('/api/identities/'+id+'/accounts',function(data) {
    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
    name: 'identities_accounts',
    search: false,columns: false,upload:false,download:false,title:'Identities',
    entries:[],
    actions:[
        {"name":"add","label":"Create Account"},
        {"name":"edit_attributes","label":"Edit Attributes","min":1,"max":1,"type":"info"},
        '','',
        {"name":"softdelete","label":"Delete Account","min":1,"max":1,"type":"danger"},
        {"name":"softrestore","label":"Restore Account","min":1,"max":1,"type":"warning"},
    ],
    count:20,
    schema:[
        {type:"hidden", name:"id"},
        {name:"system_id","label":"System",type:"select",options:"/api/systems",format:{label:"{{name}}", value:"{{id}}"},edit:false},
        {type:"text", name:"account_id", label:"Account ID", edit:false},
        {type: "select","label": "Status","name": "status","options": [{"label": "Active","value": "active"},{"label": "Deleted","value": "deleted"},{"label": "Sync Error","value": "sync_error"}]}
    ], data: data
    }).on("add",function(grid_event) {
        new gform({
            "legend":"Add Account",
            "name": "add_account",
            "fields": [
                {type:"hidden", name:"id"},
                {name:"system_id","label":"System",type:"select",options:"/api/systems",format:{label:"{{name}}", value:"{{id}}"}},
                {type:"text", name:"account_id", label:"Account ID",required:true,help:'This is the unique identifier which will be used for this account.  It may be a username or some other unique id.'}   
            ]
        }).on('save',function(form_event){
            if (form_event.form.validate()) {
                toastr.info('Processing... Please Wait')
                form_event.form.trigger('close');
                ajax.post('/api/identities/'+id+'/accounts',form_event.form.get(),function(data) {
                    gdg.add(data);
                },function(data){
                    // Do nothing?
                });
            }
        }).on('cancel',function(form_event){
            form_event.form.trigger('close');
        }).modal()
    }).on("model:softdelete",function(grid_event) {
        if (grid_event.model.attributes.status === 'deleted') {
            toastr.error("You cannot delete an account which has already been deleted!");
            return;
        }
        if (window.prompt("Are you absolutely sure you want to delete this account? This action cannot be undone!! \n\nTo continue, type the Account ID") != grid_event.model.attributes.account_id) {
            toastr.error('Not Deleting Account')
            return;
        }
        ajax.delete('/api/identities/'+id+'/accounts/'+grid_event.model.attributes.id,{},function(data) {
            grid_event.model.update(data)
        }, function(data) {
            grid_event.model.undo();
        });
    }).on("model:softrestore",function(grid_event) {
        if (grid_event.model.attributes.status !== 'deleted') {
            toastr.error("You cannot restore an account which has not been deleted!");
            return;
        }
        if (window.prompt("Are you absolutely sure you want to restore this account? This action cannot be undone!! \n\nPlease note that for certain account types, this action will create a new account with the same Account ID, but will not restore any data affiliated with a previously deleted account (Emails, etc).  Proceed Cautiosly.\n\nTo continue, type the Account ID") != grid_event.model.attributes.account_id) {
            toastr.error('Not Restoring Account')
            return;
        }
        ajax.put('/api/identities/'+id+'/accounts/'+grid_event.model.attributes.id+'/restore',grid_event.model.attributes,function(data) {
            grid_event.model.update(data)
        },function(data) {
            grid_event.model.undo();
        }); 
    }).on("model:edit_attributes",function(grid_event) {
        ajax.get('/api/systems/'+grid_event.model.attributes.system_id,function(data) {
            if (_.has(data.config.account_attributes,'attributes') && typeof data.config.account_attributes === 'object') {
                new gform({
                    "legend":"Edit Account Attributes",
                    "name": "edit_attributes",
                    "fields": data.config.account_attributes.attributes
                }).on('save',function(form_event){
                    toastr.info('Processing... Please Wait')
                    form_event.form.trigger('close');
                    grid_event.model.attributes.attributes = form_event.form.get();
                    ajax.put('/api/identities/'+id+'/accounts/'+grid_event.model.attributes.id,grid_event.model.attributes,function(data) {
                        grid_event.model.update(data)
                    },function(data) {
                        grid_event.model.undo();
                    }); 
                }).on('cancel',function(form_event){
                    form_event.form.trigger('close');
                }).modal().set(grid_event.model.attributes.attributes);
            } else {
                toastr.error("No attributes defined for accounts in this system.")
            }
        });        
    });
});

