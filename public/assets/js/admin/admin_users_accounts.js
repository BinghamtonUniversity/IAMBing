ajax.get('/api/users/'+id+'/accounts',function(data) {
    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
    item_template: gform.stencils['table_row'],
    search: false,columns: false,upload:false,download:false,title:'Users',
    entries:[],
    actions:[
        {"name":"create","label":"Add Account"},
        '',
        {"label":"Change Account Status","name":"change_status","min":1,"max":1,"type":"default"},
        '',
        {"name":"delete","label":"Delete Account"},
    ],
    count:20,
    schema:[
        {type:"hidden", name:"id"},
        {name:"system_id","label":"System",type:"select",options:"/api/systems",format:{label:"{{name}}", value:"{{id}}"}},
        {type:"text", name:"account_id", label:"Account ID", show:[{type:'matches',name:'use_default_account_id',value:false}], required:'show',help:'This is the unique identifier which will be used for this account.  It may be a username or some other unique id.'},    
        {
            "edit":false,
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
        }
    ], data: data
    }).on("model:created",function(grid_event) {
        ajax.post('/api/users/'+id+'/accounts',grid_event.model.attributes,function(data) {
            grid_event.model.update(data)
        },function(data) {
            grid_event.model.undo();
        });
    }).on("model:deleted",function(grid_event) {
        ajax.delete('/api/users/'+id+'/accounts/'+grid_event.model.attributes.id,{},
            function(data) {},
            function(data) {
            grid_event.model.undo();
        });
    }).on("model:change_status",function(grid_event) {
        var myform = new gform(
            {"fields":[
                {type:"hidden", name:"id"},
                {type: "select",label: "Status",name: "status",options: [
                    {label: "Active",value: "active"},
                    {label: "Disabled",value: "disabled"}
                ]}
            ],
            "title":"Change Account Status",
            "actions":[
                {"type":"save"}
            ]}
        ).on('save',function(e) {
            if(e.form.validate()) {
                ajax.put('/api/users/'+id+'/accounts/'+e.form.get('id'),e.form.get(), function (data) {
                    grid_event.model.set(data);
                    e.form.trigger('close');
                });
            }
        }); 
        myform.modal().set(grid_event.model.attributes);   
    });

});

