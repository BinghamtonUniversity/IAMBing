ajax.get('/api/groups/'+id+'/entitlements',function(data) {
    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
        name: 'groups_entitlements',
        search: false,columns: false,upload:false,download:false,title:'Group Entitlements',
        actions:actions,
        count:20,
        schema:[
            {type:"hidden", name:"id"},
            {name:"entitlement_id","label":"Entitlement",type:"select",options:"/api/entitlements",format:{label:"{{system.name}}: {{name}}", value:"{{id}}"}}
        ], 
        data: data
    })
    .on("model:created",function(grid_event) {
        ajax.post('/api/groups/'+id+'/entitlements',grid_event.model.attributes,function(data) {
            grid_event.model.update(data)
        },function(data) {
            grid_event.model.undo();
        });
    }).on("model:deleted",function(grid_event) {
        ajax.delete('/api/groups/'+id+'/entitlements/'+grid_event.model.attributes.entitlement_id,{},
            function(data) {},
            function(data) {
            grid_event.model.undo();
        });
    })
});


