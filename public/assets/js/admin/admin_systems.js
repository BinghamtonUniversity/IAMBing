ajax.get('/api/systems',function(data) {
    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
    item_template: gform.stencils['table_row'],
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
            "help": "This is the default template value which will be used to create this account.  (Note that the default account id can only be used to create the first account for a given user for a particular system, as further accounts with this ID will result in collisions)"
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
                    "label": "Disable Account",
                    "value": "disable"
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
			"label": "Configuration",
			"name": "config",
			"fields": [
				{
					"type": "fieldset",
					"label": "Actions",
					"name": "actions",
					"array": {
						"min": null,
						"max": null,
						"duplicate": {
							"enable": "auto",
							"label": "",
							"clone": false
						},
						"remove": {
							"enable": "auto",
							"label": ""
						}
					},
					"fields": [
						{
							"type": "select",
							"label": "Action",
							"name": "action",
							"multiple": false,
							"columns": 4,
							"required": true,
							"showColumn": true,
							"options": [
								{
									"label": "",
									"type": "optgroup",
									"options": [
										{
											"label": "Please Select an Option"
										},
                                        {
											"label": "Get Account Info",
											"value": "info"
										},
										{
											"label": "Create Account",
											"value": "create"
										},
										{
											"label": "Update Account",
											"value": "update"
										},
										{
											"label": "Delete Account",
											"value": "delete"
										},
										{
											"label": "Deactivate Account",
											"value": "deactivate"
										}
									]
								}
							]
						},
						{
							"type": "select",
							"label": "Verb",
							"name": "verb",
							"multiple": false,
							"columns": 4,
							"showColumn": true,
							"options": [
								{
									"label": "",
									"type": "optgroup",
									"options": [
										{
											"label": "GET",
											"value": "GET"
										},
										{
											"label": "POST",
											"value": "POST"
										},
										{
											"label": "PUT",
											"value": "PUT"
										},
										{
											"label": "DELETE",
											"value": "DELETE"
										}
									]
								}
							]
						},
						{
							"type": "number",
							"label": "Expected Response Code",
							"name": "response_code",
							"value": 200,
							"columns": 4,
							"required": true,
							"showColumn": true
						},
						{
							"type": "select",
							"label": "Endpoint",
							"name": "endpoint",
							"multiple": false,
							"columns": 6,
							"showColumn": true,
							"options": [
								{
									"label": "",
									"type": "optgroup",
									"path": "/api/endpoints",
									"format": {
										"label": "{{name}} - {{config.url}}",
										"value": "{{id}}"
									}
								}
							]
						},
						{
							"type": "text",
							"label": "Path",
							"name": "path",
							"columns": 6,
							"showColumn": true
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

