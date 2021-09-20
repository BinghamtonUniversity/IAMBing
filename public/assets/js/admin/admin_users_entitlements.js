ajax.get('/api/users/'+id+'/entitlements',function(data) {
    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
    item_template: gform.stencils['table_row'],
    search: false,columns: false,upload:false,download:false,title:'Entitlements',
    entries:[],
    actions:[
        // {"name":"create","label":"Add Entitlement to User"},
        {"name":"edit","label":"Update User Entitlement"},
    ],
    count:20,
    schema:[
        {type:"hidden", name:"id"},
        {name:"entitlement_id","label":"Entitlement",type:"select",options:"/api/entitlements",format:{label:"{{name}}", value:"{{id}}"},edit:false},
        {name:"type","label":"Type",type:"select",options:[{label:'Add',value:'add'},{label:'Remove',value:'remove'}]},
        {type:"switch", label: "Manually Override This Entitlement",name: "override",value:false,options:[{value:false,label:'Use Defaults (No Manual Override)'},{value:true,label:'Manual Override'}],help:'If "Manual Override" is not selected, this entitlement may be updated or deleted by default processes!'},
        {name:"override_expiration",required:true, "label":"Manual Override Expiration",type:"date", show:[{type:'matches',name:'override',value:true}],format:{input: "YYYY-MM-DD"},help:'This manual override will be enforced until the date specified, at which point it will be updated or deleted by default processes'},
        {name:"override_description", required:true, "label":"Manual Override Description",type:"textarea", show:[{type:'matches',name:'override',value:true}],limit:100}
    ], data: data
    }).on("model:created",function(grid_event) {
        ajax.post('/api/users/'+id+'/entitlements',grid_event.model.attributes,function(data) {
            grid_event.model.update(data)
        },function(data) {
            grid_event.model.undo();
        });
    }).on("model:edited",function(grid_event) {
        ajax.put('/api/users/'+id+'/entitlements/'+grid_event.model.attributes.id,grid_event.model.attributes,function(data) {
            grid_event.model.update(data)
        },function(data) {
            grid_event.model.undo();
        });
    });
});

