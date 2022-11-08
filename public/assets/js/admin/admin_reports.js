var report_form_fields = [
    {type:"hidden", name:"id"},
    {type:"text", name:"name", label:"Name",required:true},
    {type:"textarea", name:"description", label:"Description",required:false},
    {
        "type": "fieldset",
        "label": "Configuration",
        "name": "config",
        "showColumn":false,
        "fields": [
            {type:"switch",label:"Match Any / All of the Following Groups?",name:"groups_any_all",
                options:[{value:'any',label:"Match Any Groups"},{value:'all',label:"Match All Groups"}],value:false
            },
            {type:"radio",label:"Groups to Include",name:"include_group_ids",multiple:true,
                options:"/api/groups",format:{label:"{{name}} ({{type}})", value:"{{id}}"}
            },
            {type:"switch",label:"Exclude Members of Other Groups?",name:"exclude_other_groups",
                options:[{value:false,label:"False"},{value:true,label:"True"}],value:false
            },
            {type:"radio",label:"Groups to Exclude",name:"exclude_group_ids",multiple:true,
                options:"/api/groups",format:{label:"{{name}} ({{type}})", value:"{{id}}"},
                show:[{type:'matches',name:'exclude_other_groups',value:true}]
            }
        ]
    }
];

ajax.get('/api/reports',function(data) {
    data = data.reverse();
    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
    name: 'reports',
    search: false,columns: false,upload:false,download:false,title:'Reports',
    entries:[],
    sortBy: 'name',
    actions:actions,
    count:20,
    schema:report_form_fields, 
    data: data
    }).on("model:edited",function(grid_event) {
        ajax.put('/api/reports/'+grid_event.model.attributes.id,grid_event.model.attributes,function(data) {
            grid_event.model.update(data)
        },function(data) {
            grid_event.model.undo();
        });
    }).on("model:created",function(grid_event) {
        ajax.post('/api/reports',grid_event.model.attributes,function(data) {
            grid_event.model.update(data)
        },function(data) {
            grid_event.model.undo();
        });
    }).on("model:deleted",function(grid_event) {
        ajax.delete('/api/reports/'+grid_event.model.attributes.id,{},function(data) {},function(data) {
            grid_event.model.undo();
        });
    }).on("model:run_report",function(grid_event) {
        toastr.info("Please wait. Fetching the data...");
        window.open('/reports/run/'+grid_event.model.attributes.id, '_blank');
    }).on("model:run_report2",function(grid_event) {
        toastr.info("Please wait. Fetching the data...");
        window.open('/reports/run2/'+grid_event.model.attributes.id, '_blank');
    });
});