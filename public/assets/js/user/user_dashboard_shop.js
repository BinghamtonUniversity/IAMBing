ajax.get('/api/module_versions/public',function(data) {

    gdg = new GrapheneDataGrid({el:'#shoppingDataGrid',
        search: false,columns: false,upload:false,download:false,title:'Assignment',
        entries:[],
        actions:[
            {"name":"save","label":"Add To Your Assignments"},
        ],
        count:20,
        schema:[
            {type:"hidden", name:"id"},
            {type:"hidden", name:"module_id", value:id},
            {type:"text", name:"name", label:"Name"},
            {type:"text",name:"description",label:"Description"},

        ], data: data,
    }).on("model:save",function(grid_event) {
        console.log(grid_event.model.attributes);
        ajax.post('/api/users/assignments/'+grid_event.model.attributes.id,grid_event.model.attributes,function(data) {
            grid_event.model.attributes = data;
            grid_event.model.draw();
        },function(err) {
            grid_event.model.undo();
        });
    })
})