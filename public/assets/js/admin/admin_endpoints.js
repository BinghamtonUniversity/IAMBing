ajax.get('/api/endpoints',function(data) {
    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
    name: 'endpoints',
    search: false,columns: false,upload:false,download:false,title:'Systems',
    entries:[],
    actions:[
        {"name":"create","label":"New Endpoint"},
        '',
        {"name":"edit","label":"Update Endpoint"},
        '',
        {"name":"delete","label":"Delete Endpoint"}
    ],
    count:20,
    schema:[
        {label: 'Name', name:'name', required: true},
        {label: 'Configuration',type:"fieldset", name:'config', showColumn:false,template:'<dl class="dl-horizontal"><dt>URL:</dt> <dd>{{attributes.config.url}}</dd><dt>Identity Name:</dt> <dd>{{attributes.config.username}}</dd><dt>Content Type: </dt><dd>{{attributes.config.content_type}}</dd></dl>', fields:[
            {label: 'Auth Type', name:'type', type: 'select', options:[
                {label:'HTTP No Auth', value:'http_no_auth'}, 
                {label:'HTTP Basic Auth', value:'http_basic_auth'}, 
            ], required: true},    
            {label:'URL', name: 'url', required: true},
            {label:'username', required: true,show:[{type:'matches',name:'type',value:'http_basic_auth'}]},
            {label:'Password', 'name':'secret', required: true, show:[{type:'matches',name:'type',value:'http_basic_auth'}]},
            {label:'Content Type', 'name':'content_type', required: true, show:[{type:'matches',name:'type',value:'http_basic_auth'}],type:"select",options:[
                {label:"Form Data (application/x-www-form-urlencoded)",value:'application/x-www-form-urlencoded'},
                {label:"JSON (application/json)",value:'application/json'},
                {label:"XML (application/xml)",value:'application/xml'},
                {label:"Plain Text (text/plain)",value:'text/plain'},
            ],'help':'Please specify the Content Type / Data Encoding your endpoint is expecting for POST / PUT / DELETE actions.  '+
            '<div><i>Note this only applies to data which is <b>sent to</b> the endpoint, not data which is received from the endpoint.</i></div>'},  
       ]},
        {name: 'id', type:'hidden'}
], data: data
    }).on("model:edited",function(grid_event) {
        ajax.put('/api/endpoints/'+grid_event.model.attributes.id,grid_event.model.attributes,function(data) {
            grid_event.model.update(data)
        },function(data) {
            grid_event.model.undo();
        });
    }).on("model:created",function(grid_event) {
        ajax.post('/api/endpoints',grid_event.model.attributes,function(data) {
            grid_event.model.update(data)
            // grid_event.model.attributes = data;
        },function(data) {
            grid_event.model.undo();
        });
    }).on("model:deleted",function(grid_event) {
        ajax.delete('/api/endpoints/'+grid_event.model.attributes.id,{},function(data) {},function(data) {
            grid_event.model.undo();
        });
    });
});

