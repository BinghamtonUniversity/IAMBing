ajax.get('/api/identities/'+id+'/accounts',function(data) {
    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
    item_template: gform.stencils['table_row'],
    search: false,columns: false,upload:false,download:false,title:'Identities',
    entries:[],
    actions:[
        {"name":"add","label":"Create Account"},
        // {"name":"modify","label":"Modify Account","min":1,"max":1,"type":"primary"},
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
    // }).on("model:modify",function(grid_event) {
    //     if (grid_event.model.attributes.status === 'deleted') {
    //         window.alert("You cannot modify an account which has been deleted.  It must be restored firsst!");
    //         return;
    //     }
    //     new gform({
    //         "legend":"Update Account",
    //         "name": "update_account",
    //         "fields": [
    //             {name:"id", type:"hidden"},
    //             {type:"switch", label: "Manually Override This Account",name: "override",value:false,options:[{value:false,label:'Use Defaults (No Manual Override)'},{value:true,label:'Manual Override'}],help:'If "Manual Override" is not selected, this account may be updated or deleted by this identity\'s calculated entitlements!'},
    //             {name:"override_description", required:true, "label":"Manual Override Description",type:"textarea", show:[{type:'matches',name:'override',value:true}],limit:100},        
    //         ]
    //     }).on('save',function(form_event){
    //         toastr.info('Processing... Please Wait')
    //         form_event.form.trigger('close');

    //         ajax.put('/api/identities/'+id+'/accounts/'+grid_event.model.attributes.id,form_event.form.get(),function(data) {
    //             grid_event.model.update(data)
    //         },function(data) {
    //             grid_event.model.undo();
    //         }); 
    
    //     }).on('cancel',function(form_event){
    //         form_event.form.trigger('close');
    //     }).modal().set(grid_event.model.attributes)
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
    });
});

