ajax.get('/api/users/'+id+'/accounts',function(data) {
    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
    item_template: gform.stencils['table_row'],
    search: false,columns: false,upload:false,download:false,title:'Users',
    entries:[],
    actions:[
        {"name":"create","label":"Add Account to User"},
        '',
        // {"name":"edit","label":"Update Account"},
        '',
        {"name":"delete","label":"Remove Account from User"},
    ],
    count:20,
    schema:[
        {type:"hidden", name:"id"},
        {name:"system_id","label":"System",type:"select",options:"/api/systems",format:{label:"{{name}}", value:"{{id}}"}},
        {type:"text", name:"username", label:"Username",required:true},
    ], data: data
    }).on("model:created",function(grid_event) {
        ajax.post('/api/users/'+id+'/accounts',grid_event.model.attributes,function(data) {
            grid_event.model.update(data)
        },function(data) {
            grid_event.model.undo();
        });
    // }).on("model:edited",function(grid_event) {
    //     ajax.put('/api/users/'+id+'/accounts/'+grid_event.model.attributes.id,grid_event.model.attributes,function(data) {
    //         grid_event.model.update(data)
    //     },function(data) {
    //         grid_event.model.undo();
    //     });
    }).on("model:deleted",function(grid_event) {
        ajax.delete('/api/users/'+id+'/accounts/'+grid_event.model.attributes.id,{},
            function(data) {},
            function(data) {
            grid_event.model.undo();
        });
    })
});

