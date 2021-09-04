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
            "type": "fieldset",
            "label":"Configuration",
            "name": "config",
            "fields": [
                {
                    "type": "fieldset",
                    "label": "Create Account",
                    "name": "create_account",
                    "fields": [
                        {
                            "type": "select",
                            "label": "Verb",
                            "name": "verb",
                            "columns": 2,
                            "options": [
                                {
                                    "type": "optgroup",
                                    "options": [
                                        "GET","POST","PUT","DELETE"
                                    ]
                                }
                            ]
                        },
                        {
                            "type": "select",
                            "label": "Endpoint",
                            "name": "endpoint",
                            options:"/api/endpoints",format:{label:"{{name}} - {{config.url}}", value:"{{id}}"},
                            "columns": 5
                        },
                        {
                            "type": "text",
                            "label": "Path",
                            "name": "path",
                            "columns": 5,
                        },
                        {
                            "type": "checkbox",
                            "label": "Specify Payload",
                            "name": "enable_payload",
                            "value": false,
                            "showColumn": true,
                            "options": [{"value": "false"},{"value": "true"}]
                        },
                        {
                            "type": "fieldset",
                            "label":"",
                            "name": "parameters",
                            "show": [
                                {
                                    "op": "and",
                                    "conditions": [
                                        {
                                            "type": "matches",
                                            "name": "enable_payload",
                                            "value": [
                                                "true"
                                            ]
                                        }
                                    ]
                                }
                            ],
                            "array": {
                                "min": 1,
                                "max": 100,
                            },
                            "fields": [
                                {
                                    "type": "text",
                                    "label": "Key",
                                    "name": "key",
                                    "columns": 6,
                                },
                                {
                                    "type": "text",
                                    "label": "Value",
                                    "name": "value",
                                    "columns": 6,
                                }
                            ]
                        }
                    ]
                },
                {
                    "type": "fieldset",
                    "label": "Delete Account",
                    "name": "delete_account",
                    "fields": [
                        {
                            "type": "select",
                            "label": "Verb",
                            "name": "verb",
                            "columns": 2,
                            "options": [
                                {
                                    "type": "optgroup",
                                    "options": [
                                        "GET","POST","PUT","DELETE"
                                    ]
                                }
                            ]
                        },
                        {
                            "type": "select",
                            "label": "Endpoint",
                            "name": "endpoint",
                            options:"/api/endpoints",format:{label:"{{name}} - {{config.url}}", value:"{{id}}"},
                            "columns": 5
                        },
                        {
                            "type": "text",
                            "label": "Path",
                            "name": "path",
                            "columns": 5,
                        },
                        {
                            "type": "checkbox",
                            "label": "Specify Payload",
                            "name": "enable_payload",
                            "value": false,
                            "showColumn": true,
                            "options": [{"value": "false"},{"value": "true"}]
                        },
                        {
                            "type": "fieldset",
                            "label":"",
                            "name": "parameters",
                            "show": [
                                {
                                    "op": "and",
                                    "conditions": [
                                        {
                                            "type": "matches",
                                            "name": "enable_payload",
                                            "value": [
                                                "true"
                                            ]
                                        }
                                    ]
                                }
                            ],
                            "array": {
                                "min": 1,
                                "max": 100,
                            },
                            "fields": [
                                {
                                    "type": "text",
                                    "label": "Key",
                                    "name": "key",
                                    "columns": 6,
                                },
                                {
                                    "type": "text",
                                    "label": "Value",
                                    "name": "value",
                                    "columns": 6,
                                }
                            ]
                        }
                    ]
                },
                {
                    "type": "fieldset",
                    "label": "Add Entitlement",
                    "name": "add_entitlement",
                    "fields": [
                        {
                            "type": "select",
                            "label": "Verb",
                            "name": "verb",
                            "columns": 2,
                            "options": [
                                {
                                    "type": "optgroup",
                                    "options": [
                                        "GET","POST","PUT","DELETE"
                                    ]
                                }
                            ]
                        },
                        {
                            "type": "select",
                            "label": "Endpoint",
                            "name": "endpoint",
                            options:"/api/endpoints",format:{label:"{{name}} - {{config.url}}", value:"{{id}}"},
                            "columns": 5
                        },
                        {
                            "type": "text",
                            "label": "Path",
                            "name": "path",
                            "columns": 5,
                        },
                        {
                            "type": "checkbox",
                            "label": "Specify Payload",
                            "name": "enable_payload",
                            "value": false,
                            "showColumn": true,
                            "options": [{"value": "false"},{"value": "true"}]
                        },
                        {
                            "type": "fieldset",
                            "label":"",
                            "name": "parameters",
                            "show": [
                                {
                                    "op": "and",
                                    "conditions": [
                                        {
                                            "type": "matches",
                                            "name": "enable_payload",
                                            "value": [
                                                "true"
                                            ]
                                        }
                                    ]
                                }
                            ],
                            "array": {
                                "min": 1,
                                "max": 100,
                            },
                            "fields": [
                                {
                                    "type": "text",
                                    "label": "Key",
                                    "name": "key",
                                    "columns": 6,
                                },
                                {
                                    "type": "text",
                                    "label": "Value",
                                    "name": "value",
                                    "columns": 6,
                                }
                            ]
                        }
                    ]
                },
                {
                    "type": "fieldset",
                    "label": "Remove Entitlement",
                    "name": "remove_entitlement",
                    "fields": [
                        {
                            "type": "select",
                            "label": "Verb",
                            "name": "verb",
                            "columns": 2,
                            "options": [
                                {
                                    "type": "optgroup",
                                    "options": [
                                        "GET","POST","PUT","DELETE"
                                    ]
                                }
                            ]
                        },
                        {
                            "type": "select",
                            "label": "Endpoint",
                            "name": "endpoint",
                            options:"/api/endpoints",format:{label:"{{name}} - {{config.url}}", value:"{{id}}"},
                            "columns": 5
                        },
                        {
                            "type": "text",
                            "label": "Path",
                            "name": "path",
                            "columns": 5,
                        },
                        {
                            "type": "checkbox",
                            "label": "Specify Payload",
                            "name": "enable_payload",
                            "value": false,
                            "showColumn": true,
                            "options": [{"value": "false"},{"value": "true"}]
                        },
                        {
                            "type": "fieldset",
                            "label":"",
                            "name": "parameters",
                            "show": [
                                {
                                    "op": "and",
                                    "conditions": [
                                        {
                                            "type": "matches",
                                            "name": "enable_payload",
                                            "value": [
                                                "true"
                                            ]
                                        }
                                    ]
                                }
                            ],
                            "array": {
                                "min": 1,
                                "max": 100,
                            },
                            "fields": [
                                {
                                    "type": "text",
                                    "label": "Key",
                                    "name": "key",
                                    "columns": 6,
                                },
                                {
                                    "type": "text",
                                    "label": "Value",
                                    "name": "value",
                                    "columns": 6,
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

