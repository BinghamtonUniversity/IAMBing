ajax.get('/api/systems',function(data) {
    data = data.reverse();
    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
	name: 'systems',
    search: false,columns: false,upload:false,download:false,title:'Systems',
    entries:[],
    actions:[
        {"name":"create","label":"New System"},
        '',
        {"name":"edit","label":"Update System"},
        '',
        {"name":"delete","label":"Delete System"}
    ],
    count:20,
    schema:[
        {type:"hidden", name:"id"},
        {type:"text", name:"name", label:"Name",required:true},
        {
            "type": "text",
            "name": "default_account_id_template",
            "label": "Default Account ID Template",
            "edit": true,
            "value": "{{default_username}}",
            "help": "This is the default template value which will be used to create this account.  (Note that the default account id can only be used to create the first account for a given identity for a particular system, as further accounts with this ID will result in collisions)"
        },
        {
            "type": "select",
            "label": "On Remove",
            "name": "onremove",
            "options": [
                {
                    "label": "Delete Account",
                    "value": "delete"
                },
                {
                    "label": "No Action",
                    "value": "none"
                }
            ],
            "help":"When an account in this system is automatically removed (due to a lost entitlement), what should happen to the account?"
        },
        {
			"type": "fieldset",
			"label": "",
			"name": "config",
			"showColumn":false,
			"fields": [
                {
					"type": "fieldset","label": "Account Configuration","name": "account_config","fields": [
                        {type: "fieldset",label:'Attribute',columns:6,name:"account_attributes",array:{min:0,max:100},fields:[
                            {label: "Label",name: "label"},{label: "Name",name: "name"},{label: "Help Text",name: "help"},{type:"checkbox", name:"array", label:"Multi-Value Attribute", value:false, options:[{label:'Disabled',value:false},{label:'Enabled',value:true}]}
                        ]}
                    ]
                },
                {
					"type": "fieldset","label": "API Configuration","name": "api","fields": [
						{
							"type": "fieldset",
							"label": "Get Account Info",
							"name": "info",
							"fields": [
								{type:"checkbox",label:"Status",name:"enabled",value:false,options:[{label:"Enabled",value:false},{label:"Enabled",value:true}],columns:3},
								{
									"type": "select","label": "Verb","name": "verb","columns": 3,
									"options": ["GET","POST","PUT","DELETE"]
								},{
									"type": "number","label": "Expected Response Code","name": "response_code",
									"value": 200,"columns": 6,"required": true,
								},{
									"type": "select","label": "Endpoint","name": "endpoint","columns": 6,
									"options": [{"type": "optgroup","path": "/api/endpoints","format": {"label": "{{name}} - {{config.url}}","value": "{{id}}"}}]
								},{
									"type": "text","label": "Path","name": "path","columns": 6,
								}
							]
						},
						{
							"type": "fieldset",
							"label": "Create Account",
							"name": "create",
							"fields": [
								{type:"checkbox",label:"Status",name:"enabled",value:false,options:[{label:"Enabled",value:false},{label:"Enabled",value:true}],columns:3},
								{
									"type": "select","label": "Verb","name": "verb","columns": 3,
									"options": ["GET","POST","PUT","DELETE"]
								},{
									"type": "number","label": "Expected Response Code","name": "response_code",
									"value": 200,"columns": 6,"required": true,
								},{
									"type": "select","label": "Endpoint","name": "endpoint","columns": 6,
									"options": [{"type": "optgroup","path": "/api/endpoints","format": {"label": "{{name}} - {{config.url}}","value": "{{id}}"}}]
								},{
									"type": "text","label": "Path","name": "path","columns": 6,
								}
							]
						},
						{
							"type": "fieldset",
							"label": "Update Account",
							"name": "update",
							"fields": [
								{type:"checkbox",label:"Status",name:"enabled",value:false,options:[{label:"Enabled",value:false},{label:"Enabled",value:true}],columns:3},
								{
									"type": "select","label": "Verb","name": "verb","columns": 3,
									"options": ["GET","POST","PUT","DELETE"]
								},{
									"type": "number","label": "Expected Response Code","name": "response_code",
									"value": 200,"columns": 6,"required": true,
								},{
									"type": "select","label": "Endpoint","name": "endpoint","columns": 6,
									"options": [{"type": "optgroup","path": "/api/endpoints","format": {"label": "{{name}} - {{config.url}}","value": "{{id}}"}}]
								},{
									"type": "text","label": "Path","name": "path","columns": 6,
								}
							]
						},
						{
							"type": "fieldset",
							"label": "Delete Account",
							"name": "delete",
							"fields": [
								{type:"checkbox",label:"Status",name:"enabled",value:false,options:[{label:"Enabled",value:false},{label:"Enabled",value:true}],columns:3},
								{
									"type": "select","label": "Verb","name": "verb","columns": 3,
									"options": ["GET","POST","PUT","DELETE"]
								},{
									"type": "number","label": "Expected Response Code","name": "response_code",
									"value": 200,"columns": 6,"required": true,
								},{
									"type": "select","label": "Endpoint","name": "endpoint","columns": 6,
									"options": [{"type": "optgroup","path": "/api/endpoints","format": {"label": "{{name}} - {{config.url}}","value": "{{id}}"}}]
								},{
									"type": "text","label": "Path","name": "path","columns": 6,
								}
							]
						}

					]
				}
			]
		}
    ], data: data
    }).on("model:edited",function(grid_event) {
        ajax.put('/api/systems/'+grid_event.model.attributes.id,grid_event.model.attributes,function(data) {
            grid_event.model.update(data)
        },function(data) {
            grid_event.model.undo();
        });
    }).on("model:created",function(grid_event) {
        ajax.post('/api/systems',grid_event.model.attributes,function(data) {
            grid_event.model.update(data)
            // grid_event.model.attributes = data;
        },function(data) {
            grid_event.model.undo();
        });
    }).on("model:deleted",function(grid_event) {
        ajax.delete('/api/systems/'+grid_event.model.attributes.id,{},function(data) {},function(data) {
            grid_event.model.undo();
        });
    });
});

