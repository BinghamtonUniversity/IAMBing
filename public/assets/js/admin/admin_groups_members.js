ajax.get('/api/groups/'+id+'/members?simple=true',function(data) {
    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
    name: 'groups_members',
    search: false,columns: false,upload:false,download:false,title:'Identities',
    entries:[],
    actions:actions,
    count:20,
    schema:[
        {type:"hidden", name:"id"},
        {type:"identity", name:"identity_id",required:true, label:"Identity", template:"{{#attributes.simple_identity}}{{first_name}} {{last_name}}{{/attributes.simple_identity}}{{#attributes.identity}}{{first_name}} {{last_name}}{{/attributes.identity}}"},
    ], data: data
    }).on("model:created",function(grid_event) {
        ajax.post('/api/groups/'+id+'/members',grid_event.model.attributes,function(data) {
            grid_event.model.update(data)
        },function(data) {
            grid_event.model.undo();
        });
    }).on("model:deleted",function(grid_event) {
        ajax.delete('/api/groups/'+id+'/members/'+grid_event.model.attributes.identity_id,{},
            function(data) {},
            function(data) {
            grid_event.model.undo();
        });
    })
});

// Built-In Events:
//'edit','model:edit','model:edited','model:create','model:created','model:delete','model:deleted'


