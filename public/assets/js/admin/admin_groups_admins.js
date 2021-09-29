ajax.get('/api/groups/'+id+'/admins?simple=true',function(data) {
    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
    item_template: gform.stencils['table_row'],
    search: false,columns: false,upload:false,download:false,title:'Group Admins',
    entries:[],
    actions:[
        {"name":"create","label":"Add Admin to Group"},
        '','',
        {"name":"delete","label":"Remove Admin from Group"},
    ],
    count:20,
    schema:[
        {type:"hidden", name:"id"},
        {type:"user", name:"user_id",required:true, label:"User", template:"{{#attributes.simple_user}}{{first_name}} {{last_name}}{{/attributes.simple_user}}{{#attributes.user}}{{first_name}} {{last_name}}{{/attributes.user}}"},
    ], data: data
    }).on("model:created",function(grid_event) {
        ajax.post('/api/groups/'+id+'/admins',grid_event.model.attributes,function(data) {
            grid_event.model.update(data)
        },function(data) {
            grid_event.model.undo();
        });
    }).on("model:deleted",function(grid_event) {
        ajax.delete('/api/groups/'+id+'/admins/'+grid_event.model.attributes.user_id,{},
            function(data) {},
            function(data) {
            grid_event.model.undo();
        });
    })
});

// Built-In Events:
//'edit','model:edit','model:edited','model:create','model:created','model:delete','model:deleted'


