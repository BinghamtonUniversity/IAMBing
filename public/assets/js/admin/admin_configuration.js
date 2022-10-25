gform.options = {autoFocus:false};
$('#adminDataGrid').html(`
    <div class='row'>
        <div class='col-xs-12'>
            <h3>Default Username Template</h3>
            <div class="alert alert-info">
                This is the mustache template for generating default usernames
            </div>
            <div class="default_username_template"></div>
        </div>

        <div class='col-xs-12'>
            <h3>Default Email Domain</h3>
            <div class="alert alert-info">
                This domain will be appended to the default username when generating the default email addresses.  (default_username@example.com)
            </div>
            <div class="default_email_domain"></div>
        </div>

        <div class='col-xs-12'>
            <h3>Group Action Queue Email Settings</h3>
            <div class="alert alert-info">
                This is the email template which will be used when an identity is added to the group action queue would a delayed "remove" action.
            </div>
            <div class="action_queue_remove_email"></div>
        </div>


        <div class='col-xs-12'>
            <h3>Identity Username Availability Check</h3>
            <div class="usernames"></div>
        </div>

        <div class='col-xs-12'>
            <h3>Additional Attributes</h3>
            <div class="alert alert-info">
                These are the identity attributes which can be populated in IAMBing
            </div>
            <div class="identity_attributes"></div>
        </div>

        <div class='col-xs-12'>
            <h3>Identity Unique IDs</h3>
            <div class="alert alert-info">
                These are the unique identity IDs which can be populated in IAMBing
            </div>
            <div class="identity_unique_ids"></div>
        </div>

        <div class='col-xs-12'>
            <h3>Affiliations</h3>
            <div class="alert alert-info">
                These are the various affiliations a person may have.  <a href="https://infrastructure.tamu.edu/directory/attribute/attribute_eduPersonAffiliation.html">More Info</a>
            </div>
            <div class="affiliations"></div>
        </div>

        <div class='col-xs-12'>
            <div id= 'manage_jobs'>
                <h3>Database / Job Queue Reset</h3>
                <div class="alert alert-info">
                Refresh the IAMBing Redis Job Queue! (Seriously guys, this is pretty serious)
                </div>
                <div class="btn btn-danger nuke_redis">Flush Job Queue (Redis)</div>
            </div>
        </div>
    </div>
`);

if(!auth_user_perms.some( e=> {return e=='manage_jobs'} )){
    $('#manage_jobs').hide();
}

var gforms = {};
gforms.default_username_template = new gform(
    {"fields":[
        {type:"hidden", name:"id"},
        {type:"hidden", name:"name", value:'default_username_template'},
        {type:"text", name:"config", label:"Default username Template", edit:true},
    ],
    "el":".default_username_template",
    "actions":[
        {"type":"save","label":"Save","modifiers":"btn btn-primary"}
    ]
}
).on('save',function(e) {
    toastr.info('Processing... Please Wait')
    var form_data = e.form.get();
    ajax.put('/api/configuration/'+form_data.name,form_data,function(data) {
        toastr.success('Configuration Updated');
    });
});

gforms.default_email_domain = new gform(
    {"fields":[
        {type:"hidden", name:"id"},
        {type:"hidden", name:"name", value:'default_email_domain'},
        {type:"text", name:"config", label:"Default Email Domain", edit:true,placeholder:"example.com"},
    ],
    "el":".default_email_domain",
    "actions":[
        {"type":"save","label":"Save","modifiers":"btn btn-primary"}
    ]
}
).on('save',function(e) {
    toastr.info('Processing... Please Wait')
    var form_data = e.form.get();
    ajax.put('/api/configuration/'+form_data.name,form_data,function(data) {
        toastr.success('Configuration Updated');
    });
});

gforms.action_queue_remove_email = new gform(
    {"fields":[
        {type:"hidden", name:"id"},
        {type:"hidden", name:"name", value:'action_queue_remove_email'},
        {type: "fieldset",label:'Email Configuration',columns:12,name: "config",fields: [
            {type:"text", name:"to", label:"Email To:", edit:true,raw:true,value:"{{default_email}}",help:"This is a comma separated list of email addresses defined using mustache syntax"},
            {type:"text", name:"cc", label:"Email Cc:", edit:true,raw:true,value:"",help:"This is a comma separated list of email addresses defined using mustache syntax"},
            {type:"text", name:"bcc", label:"Email Bcc:", edit:true,raw:true,value:"",help:"This is a comma separated list of email addresses defined using mustache syntax"},
            {type:"text", name:"subject", label:"Email Subject", edit:true, raw:true, value:"Notification of Account Changes"},
            {type:"textarea", name:"body", label:"Email Body", edit:true, raw:true,value:
`{{first_name}} {{last_name}},

Your affiliation(s) have recently changed, and you are no a member of the following population(s) effective the dates specified below:
{{#future_impact.lost_groups}}
    * {{name}} {{#scheduled_date}}({{scheduled_date}}){{/scheduled_date}}
{{/future_impact.lost_groups}}

With this change, you will lose access to the following services:
{{#future_impact.lost_entitlements}}
    * {{name}}
{{/future_impact.lost_entitlements}}

These changes will impact the following accounts which currently 
belong to you:
{{#future_impact.impacted_accounts}}
    * {{account_id}} ({{system.name}})
{{/future_impact.impacted_accounts}}`
            },
    ]}],
    "el":".action_queue_remove_email",
    "actions":[
        {"type":"save","label":"Save","modifiers":"btn btn-primary"}
    ]
}
).on('save',function(e) {
    toastr.info('Processing... Please Wait')
    var form_data = e.form.get();
    ajax.put('/api/configuration/'+form_data.name,form_data,function(data) {
        toastr.success('Configuration Updated');
    });
});

gforms.default_email_domain = new gform(
    {"fields":[
        {type:"hidden", name:"id"},
        {type:"hidden", name:"name", value:'default_email_domain'},
        {type:"text", name:"config", label:"Default Email Domain", edit:true,placeholder:"example.com"},
    ],
    "el":".default_email_domain",
    "actions":[
        {"type":"save","label":"Save","modifiers":"btn btn-primary"}
    ]
}
).on('save',function(e) {
    toastr.info('Processing... Please Wait')
    var form_data = e.form.get();
    ajax.put('/api/configuration/'+form_data.name,form_data,function(data) {
        toastr.success('Configuration Updated');
    });
});


gforms.identity_attributes = new gform(
    {"fields":[
        {type:"hidden", name:"id"},
        {type:"hidden", name:"name", value:'identity_attributes'},
        {type: "fieldset",label:'Attribute',columns:3,name: "config",array:{max:100},fields: 
            [{label: "Label",name: "label"},{label: "Name",name: "name"},{label: "Help Text",name: "help"},{type:"checkbox", name:"array", label:"Multi-Value Attribute", value:false, options:[{label:'Disabled',value:false},{label:'Enabled',value:true}]}]
    }],
    "el":".identity_attributes",
    "actions":[
        {"type":"save","label":"Save","modifiers":"btn btn-primary"}
    ]
}
).on('save',function(e) {
    toastr.info('Processing... Please Wait')
    var form_data = e.form.get();
    ajax.put('/api/configuration/'+form_data.name,form_data,function(data) {
        toastr.success('Configuration Updated');
    });
});

gforms.identity_unique_ids = new gform(
    {"fields":[
        {type:"hidden", name:"id"},
        {type:"hidden", name:"name", value:'identity_unique_ids'},
        {type: "fieldset",label:'Unique ID',columns:3,name: "config",array:{max:100},fields: 
            [{label: "Label",name: "label",},{label: "Name",name: "name"}]
	    }],
    "el":".identity_unique_ids",
    "actions":[
        {"type":"save","label":"Save","modifiers":"btn btn-primary"}
    ]
}
).on('save',function(e) {
    toastr.info('Processing... Please Wait')
    var form_data = e.form.get();
    ajax.put('/api/configuration/'+form_data.name,form_data,function(data) {
        toastr.success('Configuration Updated');
    });
});

gforms.affiliations = new gform(
    {"fields":[
        {type:"hidden", name:"id"},
        {type:"hidden", name:"name", value:'affiliations'},
        {label: "Affiliation",name: "config", array:{max:100},columns:3}
	],
    "el":".affiliations",
    "actions":[
        {"type":"save","label":"Save","modifiers":"btn btn-primary"}
    ]
}
).on('save',function(e) {
    toastr.info('Processing... Please Wait')
    var form_data = e.form.get();
    ajax.put('/api/configuration/'+form_data.name,form_data,function(data) {
        toastr.success('Configuration Updated');
    });
});

gforms.username_availability = new gform(
        {
        "fields":[
            {type:"hidden", name:"id"},
            {type:"hidden", name: "name",value:'username_availability'},
            {type: "fieldset",label:'Config',columns:12,name: "config",fields: 
                [
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
                        "label": "Expected Available Response Code",
                        "name": "available_response",
                        "value": 404,
                        "columns": 4,
                        "required": true,
                        "showColumn": true
                    },
                    {
                        "type": "number",
                        "label": "Expected Not Available Response Code",
                        "name": "not_available_response",
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
	],
    "el":".usernames",
    "actions":[
        {"type":"save","label":"Save","modifiers":"btn btn-primary"}
    ]
}
).on('save',function(e) {
    toastr.info('Processing... Please Wait')
    var form_data = e.form.get();
    ajax.put('/api/configuration/'+form_data.name,form_data,function(data) {
        toastr.success('Configuration Updated');
    });
});

$('.nuke_redis').on('click',function() {
    toastr.warning('Redis Job Queue flush in progress...');
    ajax.get('/api/configuration/refresh/redis',function(data) {
        toastr.success('Redis Job Queue has been reset!');
    },
    function(data){
        toastr.error(data.responseJSON.message);
    });
});

ajax.get('/api/configuration/',function(data) {
    _.each(data,function(item) {
        if (_.has(gforms,item.name)) {
            gforms[item.name].set({config:item.config})
        }
    })
});
