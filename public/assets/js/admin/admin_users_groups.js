ajax.get('/api/users/'+id+'/groups',function(data) {
    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
    item_template: gform.stencils['table_row'],
    search: false,columns: false,upload:false,download:false,title:'Users',
    entries:[],
    actions:[
        {"name":"create","label":"Add Group to User"},
        '',
        '',
        {"name":"delete","label":"Remove Group from User"},
    ],
    count:20,
    schema:[
        {type:"hidden", name:"id"},
        {name:"group_id","label":"Group",type:"select",options:"/api/groups",format:{label:"{{name}}", value:"{{id}}"}},
    ], data: data
    }).on("model:created",function(grid_event) {
        ajax.post('/api/groups/'+grid_event.model.attributes.group_id+'/members',{user_id:id},function(data) {
            grid_event.model.update(data)
        },function(data) {
            grid_event.model.undo();
        });
    }).on("model:deleted",function(grid_event) {
        ajax.delete('/api/groups/'+grid_event.model.attributes.group_id+'/members/'+id,{},
            function(data) {},
            function(data) {
            grid_event.model.undo();
        });
    })
});

