ajax.get('/api/entitlements',function(data) {
    data = data.reverse();
    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
    name: 'entitlements',
    search: false,columns: false,upload:false,download:false,title:'Entitlements',
    entries:[],
    actions:actions,
    count:20,
    schema:[
        {type:"hidden", name:"id"},
        {name:"system_id","label":"System",type:"select",options:"/api/systems",format:{
            label:"{{name}}", value:"{{id}}"}
        },
        {name:"subsystem","label":"Subsystem",type:"select",options:[
            {type:"optgroup",options: [{label: "N/A",value:null}]},
            {type: "optgroup",path: "/api/systems/subsystems",format:{label:"{{system}}: {{subsystem}}", value:"{{subsystem}}"}}
        ]},
        
        {type:"text", name:"name", label:"Name",required:true},
        {type:"switch", label:"Allow Manual Override: Add Entitlement",name: "override_add",value:false,options:[{value:false,label:'Disabled'},{value:true,label:'Enabled'}]},
        {type:"switch", label:"End User Visibility",name: "end_user_visible",value:true,options:[{value:false,label:'Not Visible'},{value:true,label:'Visible'}]},
        {type:"switch", label:"Require Prerequisite",name: "require_prerequisite",value:false,options:[{value:false,label:'Do Not Require'},{value:true,label:'Require'}]},
        {type:"select", label:"Prerequisites", name:"prerequisites",array:{min:1,max:100},options:data,format:{label:"{{system.name}}: {{name}}",value:"{{id}}"},show:[{type:'matches',name:'require_prerequisite',value:true}]}
    ], data: data
    }).on("model:edited",function(grid_event) {
        ajax.put('/api/entitlements/'+grid_event.model.attributes.id,grid_event.model.attributes,function(data) {
            grid_event.model.update(data)
        },function(data) {
            grid_event.model.undo();
        });
    }).on("model:created",function(grid_event) {
        ajax.post('/api/entitlements',grid_event.model.attributes,function(data) {
            grid_event.model.update(data)
            // grid_event.model.attributes = data;
        },function(data) {
            grid_event.model.undo();
        });
    }).on("model:manage_groups",function(grid_event) {
        window.location = '/entitlements/'+grid_event.model.attributes.id+'/groups';
    }).on("model:deleted",function(grid_event) {
        ajax.delete('/api/entitlements/'+grid_event.model.attributes.id,{},function(data) {},function(data) {
            grid_event.model.undo();
        });
    }).on('model:overrides',function(grid_event){
        ajax.get('/api/entitlements/'+grid_event.model.attributes.id+"/overrides",function(data) {
            window.location = '/entitlements/'+grid_event.model.attributes.id+'/overrides';
        },function(data) {});
    });

});

