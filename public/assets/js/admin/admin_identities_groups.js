ajax.get('/api/identities/'+id+'/groups',function(data) {
    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
    name: 'identities_groups',
    search: false,columns: false,upload:false,download:false,title:'Identities',
    entries:[],
    actions:[
        {"name":"create","label":"Add Group to Identity"},
        '',
        '',
        {"name":"delete","label":"Remove Group from Identity"},
    ],
    count:20,
    schema:[
        {type:"hidden", name:"id"},
        {name:"group_id","label":"Group",type:"select",options:"/api/groups",format:{label:"{{name}}", value:"{{id}}"}},
    ], data: data
    }).on("model:created",function(grid_event) {
        ajax.post('/api/groups/'+grid_event.model.attributes.group_id+'/members/'+id,{},function(data) {
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

