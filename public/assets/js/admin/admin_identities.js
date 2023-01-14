var mymodal = new gform(
    {"fields":[
        {name:'output',value:'',type:'output',label:''}
    ],
    "title":"Info",
    "actions":[]}
);

gform.options = {autoFocus:false};
identity_form_attributes = [
    {type:"hidden", name:"id", label: 'id'},
    {type:"switch", name:"sponsored", label:"Sponsored", value:false, columns:6, options:[{label:'Default',value:false},
    {label:'Sponsored',value:true}]},
    {   type:'select',
        name:'type',
        label:"Type",
        required:true,
        options:[
            {label:'Please select'},    
            {value:"person",label:'Person'},
            {value:"organization",label:'Organization'},
            {value:"service",label:'Service'}
        ]
    },
    {type:"text", name:"iamid", label: 'IAM ID', edit:false},
    {type:"text", name:"first_name", label:"First Name", required:true},
    {type:"text", name:"last_name", label:"Last Name", required:true},
    {type:"text", name:"default_username", label:"Default username", required:false, help:'Leave blank to define automatically'},
    {type:"text", name:"default_email", label:"Default Email Address", required:false, help:'Leave blank to define automatically'},
    {type:"identity", name:"sponsor_identity_id",required:false, label:"Sponsor",show:[{type:'matches',name:'sponsored',value:true}]},
];

$('#adminDataGrid').html(Ractive({template:`
<div class="row">
    <div class="col-sm-3 actions">
        <div class="row">
            <div class="col-sm-12">
                <div class="identity-search"></div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="btn btn-success identity-new" style="width:100%;">Create New Identity</div><br><br>
            </div>
        </div>
    </div>
    <div class="col-sm-9 identity-view" style="display:none;">
        <div class="row">
            <div class="col-sm-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <div class="dropdown">
                                <button class="btn btn-primary dropdown-toggle btn-xs pull-right" type="button" id="dropdownMenu1" data-toggle="dropdown">
                                    Identity Actions
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right" style="margin-top:25px;">
                                    {{#actions}}
                                        <li><a style="cursor:pointer;" class="{{modifiers}} identity-action" data-action="{{action}}">{{label}}</a></li>
                                    {{/actions}}
                                </ul>
                            </div>
                            Identity
                        </h3>
                    </div>
                    <div class="panel-body identity-edit"></div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="identityinfo-column"></div>
            </div>
        </div>
    </div>
</div>
<style>
.label {
    display:block;
    margin-bottom:5px;
}
.panel-title>a {
    color:white;
}
</style>
`,data:{actions:actions}}).toHTML());

identitylist_template = `
{{^identities.length}}<div class="alert alert-warning">No Matches</div>{{/identities.length}}
<div class="list-group">
    {{#identities}}
        <a href="javascript:void(0);" class="list-group-item identity" data-id="{{id}}">
            <div class="badge pull-right">{{default_username}}</div>
            {{first_name}} {{last_name}}
        </a>
    {{/identities}}
</div>
`;

identityinfo_column_template = `
{{#future_impact}}
    <div class="btn btn-xl btn-danger future-impact-btn" style="width:100%;margin-bottom:15px;font-weight:bold;">
        Warning: Pending Group Actions
    </div>
{{/future_impact}}
<!-- Accounts -->
<div class="panel panel-default">
<div class="panel-heading"><h3 class="panel-title">
    {{#auth_user_perms}}
        {{#if . == "manage_identity_accounts"}}
            <a class="btn btn-primary btn-xs pull-right" href="/identities/{{id}}/accounts">Manage Accounts</a>
        {{/if}}
    {{/auth_user_perms}}
    Systems / Accounts
</h3></div>
<div class="panel-body identity-accounts">{{>identity_accounts_template}}</div>
</div>

<!-- Groups -->
<div class="panel panel-default">
    <div class="panel-heading"><h3 class="panel-title">
        {{#if auth_user_perms.includes("manage_groups") || auth_user_perms.includes("view_groups") }}
            <a class="btn btn-primary btn-xs pull-right" href="/identities/{{id}}/groups">Manage Groups</a>
        {{/if}}
        Groups
    </h3></div>
    <div class="panel-body identity-groups">{{>identity_groups_template}}</div>
</div>

<!-- Entitlements -->
<div class="panel panel-default">
<div class="panel-heading"><h3 class="panel-title">
    {{#auth_user_perms}}
        {{#if . == "override_identity_entitlements"}}
            <a class="btn btn-primary btn-xs pull-right" href="/identities/{{id}}/entitlements">Override Entitlements</a>
        {{/if}}
    {{/auth_user_perms}}

    Entitlements
</h3></div>
<div class="panel-body identity-entitlements">{{>identity_entitlements_template}}</div>
</div>

<!-- Affiliations -->
<div class="panel panel-default">
    <div class="panel-heading"><h3 class="panel-title">Affiliations</h3></div>
    <div class="panel-body identity-affiliations">{{>identity_affiliations_template}}</div>
</div>

<!-- Sponsored Identities -->
<div class="panel panel-default">
<div class="panel-heading"><h3 class="panel-title">Sponsored Identities</h3></div>
<div class="panel-body identity-groups">{{>sponsored_identities_template}}</div>
</div>

<!-- Permissions -->
<div class="panel panel-default">
<div class="panel-heading">
    <h3 class="panel-title">Permissions<a class="btn btn-primary btn-xs pull-right" data-toggle="collapse" href=".identity-site-permissions">Manage Permissions</a></h3>
</div>
    <div class="panel-body identity-site-permissions panel-collapse collapse"></div>
</div>
`;

identity_groups_template = `
<div style="font-size:20px;">
{{#groups}}
    <a class="label label-default label-block" href="/groups/{{id}}/members">{{name}}</a>&nbsp;
{{/groups}}
</div>
{{^groups}}
    <div class="alert alert-warning">No Group Memberships</div>
{{/groups}}
`;

sponsored_identities_template = `
<div style="font-size:20px;">
{{#sponsored_identities}}
    <a href="/identities/{{id}}" class="label label-default">
        <div class="label label-primary pull-right">{{default_username}}</div>
        {{first_name}} {{last_name}}
    </a>
{{/sponsored_identities}}
</div>
{{^sponsored_identities}}
    <div class="alert alert-warning">No Sponsored Identities</div>
{{/sponsored_identities}}
`;


identity_affiliations_template = `
<div style="font-size:20px;">
    {{#each affiliations: num}}
        {{#if num === 0}}
            <div class="label label-primary">Primary: {{#.}}{{.}}{{/.}}{{^.}}None{{/.}}</div>
        {{else}}
            <div class="label label-default">{{.}}</div>
        {{/if}}
    {{/each}}
</div>
{{^affiliations}}
    <div class="alert alert-warning">No Affiliations</div>
{{/affiliations}}
`;

entitlements_template = `
{{#pivot.override}}
    {{#pivot.type === 'remove'}}
        <span class="label label-danger label-block">
            {{name}}
            <div class="tinytext">
                {{#pivot.expire}}(Manually Removed Until: {{pivot.expiration_date}}){{/}}
                {{^pivot.expire}}(Manually Removed: No Expiration){{/}}
            </div>
        </span>
    {{/}}
    {{#pivot.type === 'add'}}
        <span class="label label-success label-block">
            {{name}}
            <div class="tinytext">
                {{#pivot.expire}}(Manually Added Until: {{pivot.expiration_date}}){{/}}
                {{^pivot.expire}}(Manually Added: No Expiration){{/}}
            </div>
        </span>
    {{/}}
    {{/}}
    {{^pivot.override}}
    <div class="label label-default label-block">{{name}}</div>
{{/}}
`;

identity_entitlements_template = `
<!--
    <div class="well well-sm" style="margin-bottom:10px;"><i class="fa fa-info-circle"></i> These are the entitlements as they are currently enforced, taking into account any manual overrides which may deviate from the default entitlement calculations</div>
-->
<div style="font-size:20px;">
    {{#each entitlements_by_subsystem: system}}
        <h4 style="margin-bottom:4px;border:solid;border-width:0px 0px 1px 0px;border-color:#ccc6;">{{system}}</h4>
        {{#.entitlements}} 
            {{>entitlements_template}}
        {{/}}
        {{#each subsystems: subsystem}}
            <h5 style="margin-bottom:0px;">{{subsystem}}</h5>
            {{#.}}
                {{>entitlements_template}}
            {{/}}
        {{/each}}
    {{/each}}
</div>
{{^identity_entitlements}}
    <div class="alert alert-warning">No Entitlements</div>
{{/identity_entitlements}}

<h5><a class="btn btn-default btn-xs" data-toggle="collapse" href=".show-hide-calculated" style="width:100%;">Show / Hide Default Entitlements</a></h5>
<div class="show-hide-calculated panel-collapse collapse">
    <div class="well well-sm" style="margin-bottom:10px;"><i class="fa fa-info-circle"></i> These are the default entitlements which are automatically calculated based on group memberships</div>
    <div style="font-size:15px;">
        {{#calculated_entitlements}}
            <div class="label label-default" style="display:inline-block;margin:0px 5px 5px 0px;">{{name}}</div>
        {{/calculated_entitlements}}
    </div>
</div>
`;

identity_accounts_template = `
<!--
<div class="well well-sm"  style="margin-bottom:10px;"><i class="fa fa-info-circle"></i> These are the accounts which are currently assigned to this identity, which facilitate their entitlements.</div>
-->
<div style="font-size:20px;">
    {{#systems_with_accounts_history}}
            <a href="#" class="account-info-btn label {{#if pivot.status === 'active'}}label-default{{elseif pivot.status === 'sync_error'}}label-warning{{elseif pivot.status === 'disabled'}}label-danger{{else}}label-danger{{/if}}" data-id="{{pivot.id}}">
                {{name}} / {{pivot.account_id}}
                {{#pivot.status === 'sync_error'}}<div class="tinytext">(Sync Error)</div>{{/}}
                {{#pivot.status === 'disabled'}}<div class="tinytext">(Disabled)</div>{{/}}
                {{#pivot.status === 'deleted'}}<div class="tinytext">(Deleted)</div>{{/}}
            </a>
    {{/systems_with_accounts_history}}
</div>
{{^systems}}
    <div class="alert alert-warning">No Accounts</div>
{{/systems}}
`;

// Create New Identity
app.click('.identity-new',function() {
    new gform(
        {"fields":identity_form_attributes,
        "title":"Create New Identity",
        "actions":[
           {"type":"save"}
        ]}
    ).modal().on('save',function(form_event) {
        if(form_event.form.validate())
        {
            ajax.post('/api/identities', form_event.form.get(), function (data) {
                manage_identity(data.id);
                form_event.form.trigger('close');
            });
        }else{
            toastr.error("Please fill the required fields!");
        }
    });
})

var manage_identity = function(identity_id) {
    app.data.identity_id = identity_id;
    if (identity_id != null && identity_id != '') {
        ajax.get('/api/identities/'+identity_id,function(data) {
            data.auth_user_perms = auth_user_perms;
            window.history.pushState({},'','/identities/'+identity_id);
            $('.identity-view').show();
            $('.identityinfo-column').html(Ractive({
                template:identityinfo_column_template,
                partials: {
                    identity_groups_template:identity_groups_template,
                    identity_entitlements_template:identity_entitlements_template,
                    entitlements_template:entitlements_template,
                    identity_affiliations_template:identity_affiliations_template,
                    identity_accounts_template:identity_accounts_template,
                    sponsored_identities_template,
                },
                data:data
            }).toHTML());

            // Edit Identity
            identity_form = new gform(
                {
                    "fields":identity_form_attributes
                        .map(d=>{
                                if (d.name =='iamid') return d;
                                d.edit = auth_user_perms.some(e=> {return e === "manage_identities"})
                                return d;
                }),
                "el":".identity-edit",
                "data":data,
                "actions": actions
                }
            ).on('delete',function(form_event) {
                form_data = form_event.form.get();
                if (prompt('Please enter the IAM ID of this identity to confirm deletion') == form_data.iamid){
                    ajax.delete('/api/identities/'+form_data.id,{},function(data) {
                        $('.identity-view').hide();
                    });
                } else {
                    toastr.error('Delete Action Canceled');
                }
            }).on('merge_identity',function(form_event) {
                form_data = form_event.form.get();
                target_identity = form_data.id;
                new gform(
                    {"fields":[{
                        "type": "identity",
                        "label": "Source Identity",
                        "name": "source_identity",
                        "required":true,
                    },{type:"checkbox", name:"delete", label:"Delete Source Identity", value:false,help:"By checking this box, the `source` identity will be irretrievably deleted from IAMBing."},
                    {type:"output",parse:false,value:'<div class="alert alert-danger">This action will migrate/transfer all assignments from the source identity to the specified target identity.  This is a permanent and "undoable" action.</div>'}],
                    "title":"Merge Identity",
                    "actions":[
                        {"type":"cancel"},
                        {"type":"button","label":"Commit Merge","action":"save","modifiers":"btn btn-danger"},
                    ]}
                ).modal().on('save',function(merge_form_event) {
                    var merge_form_data = merge_form_event.form.get();
                    if(form_event.form.validate() && merge_form_data.source_identity !== '')
                    {
                        if (merge_form_data.source_identity == target_identity) {
                            toastr.error("You cannot merge an identity into itself!")
                        } else {
                            if (confirm("Are you sure you want to merge these identities?  This action cannot be undone!")) {
                                ajax.put('/api/identities/'+merge_form_data.source_identity+'/merge_into/'+target_identity,
                                    { delete:merge_form_data.delete},
                                    function (data) {
                                    merge_form_event.form.trigger('close');
                                    if (_.has(data,'errors')) {
                                        toastr.error('One or more errors occurred.')
                                        console.log(data.errors);
                                        window.alert(data.errors.join("\n"))
                                    } else {
                                        toastr.success('Identity Merge Successful!');
                                    }
                                });
                            }else{
                                toastr.warning("Aborting the merge...");
                            }
                        }
                    }
                }).on('cancel',function(merge_form_event) {
                    merge_form_event.form.trigger('close');
                });
            }).on('save',function(form_event) {
                if(form_event.form.validate()) {
                    form_data = form_event.form.get();
                    ajax.put('/api/identities/' + form_data.id, form_data, function (data) {
                        manage_identity(data.id);
                        if (data.sync_errors === true) {
                            toastr.success("Recalculate / Sync Success!");
                        } else {
                            toastr.error("Recalculate / Sync Error");
                            mymodal.modal().set({output:'<pre>'+JSON.stringify(data.sync_errors,null,2)+'</pre>'});
                        }
                    });    
                }
            }).on('login',function(form_event) {
                if (confirm("Are you sure you want to continue?")) {
                    form_data = form_event.form.get();
                    ajax.post('/api/login/'+form_data.id,{},function(data) {
                        window.location = '/';
                    });
                }
            }).on('recalculate',function(form_event) {
                form_data = form_event.form.get();
                ajax.get('/api/identities/'+identity_id+'/recalculate',function(data) {
                    manage_identity(data.id);
                    if (data.sync_errors === true) {
                        toastr.success("Recalculate / Sync Success!");
                    } else {
                        toastr.error("Recalculate / Sync Error");
                        mymodal.modal().set({output:'<pre>'+JSON.stringify(data.sync_errors,null,2)+'</pre>'});
                    }
                });
            }).on('future_impact',function(form_event) {
                form_data = form_event.form.get();
                ajax.get('/api/identities/'+identity_id+'/future_impact?all=true',function(data) {
                    if (typeof data.future_impact == 'object') {
                        var future_impact_template = `
                        <div class="btn btn-primary pull-right future-impact-msg-btn">View End User Message</div>
                        <br>
                        <h3 style="margin-top:0px;">Groups</h3>
                        <div class="alert alert-info">{{first_name}} {{last_name}} has pending removal actions against the following groups:</div>
                        <ul>
                        {{#future_impact.lost_groups}}
                            <li>{{name}} {{#scheduled_date}}({{scheduled_date}}){{/scheduled_date}}</li>
                        {{/future_impact.lost_groups}}
                        </ul>

                        <h3>Entitlements</h3>
                        <div class="alert alert-info">When groups (above) have been removed, {{first_name}} {{last_name}} will lose the following entitlements:</div>
                        <ul>
                        {{#future_impact.lost_entitlements}}
                            <li>{{name}}</li>
                        {{/future_impact.lost_entitlements}}
                        </ul>

                        <h3>Impacted Accounts</h3>
                        <div class="alert alert-info">Loss of the entitlements (above) will impact the following accounts:</div>
                        <ul>
                        {{#future_impact.impacted_accounts}}
                            <li>{{account_id}} ({{system.name}})</li>
                        {{/future_impact.impacted_accounts}}
                        </ul>
                        `;
                        var html = Ractive({template:future_impact_template,data:data}).toHTML();
                        mymodal.modal().set({output:html});
                    } else {
                        toastr.success("No Future Impact Detected");
                    } 
                });
            }).on('future_impact_msg',function(form_event) {
                form_data = form_event.form.get();
                ajax.get('/api/identities/'+identity_id+'/future_impact_msg',function(data) {
                    if (typeof data.future_impact_msg === 'object') {
                        data.future_impact_msg.body = data.future_impact_msg.body.replace(/\n/g,'<br>').replace(/\s\s/g,'&nbsp;&nbsp;');
                        var template =
`<div class="alert alert-info">
    Please note that the message below only displays changes to 
    entitlements which are end-user visible. The identity may be losing additional entitlements 
    which are not displayed here.
</div>
<div class="panel panel-default">
    <ul class="list-group">
        <li class="list-group-item"><b>To:</b> {{#to}}{{#email}}{{email}}{{/email}}{{^email}}{{.}}{{/email}}, {{/to}}</li>
        {{#cc.0}}<li class="list-group-item"><b>Cc:</b> {{#cc}}{{#email}}{{email}}{{/email}}{{^email}}{{.}}{{/email}}, {{/cc}}</li>{{/cc.0}}
        {{#bcc.0}}<li class="list-group-item"><b>Bcc:</b> {{#bcc}}{{#email}}{{email}}{{/email}}{{^email}}{{.}}{{/email}}, {{/bcc}}</li>{{/bcc.0}}
        <li class="list-group-item"><b>Subject:</b> {{subject}}</li>
        <li class="list-group-item">{{{body}}}</li>
    </ul>
</div>`;
                        var html = Ractive({template:template,data:data.future_impact_msg}).toHTML();
                        mymodal.modal().set({output:html});
                    } else {
                        mymodal.modal().set({output:'<div class="alert alert-info">Please note that the message below only displays changes to entitlements which are end-user visible. The identity may be losing additional entitlements which are not displayed here.</div><div class="alert alert-info">No Impacts Detected</div>'});
                    } 
                });
            }).on('view_logs',function(form_event){
                form_data = form_event.form.get();
                window.location = form_data.id+"/logs";
            });
            // end
            // Edit Permissions
            new gform(
                {"fields":[
                    {
                        "type": "radio",
                        "label": "Permissions",
                        "name": "permissions",
                        "multiple": true,
                        "edit": auth_user_perms.some(e=> {return e === "manage_identity_permissions"}),
                        "options": [
                            {   "label":"View Identities",
                                "value":"view_identities"
                            },
                            {
                                "label": "Manage Identities",
                                "value": "manage_identities"
                            },
                            {
                                "label": "Manage Identity Permissions",
                                "value": "manage_identity_permissions"
                            },
                            {
                                "label": "Merge Identity",
                                "value": "merge_identities"
                            },
                            {
                                "label": "Manage Accounts",
                                "value": "manage_identity_accounts"
                            },
                            {
                                "label": "Override Identity Entitlements",
                                "value": "override_identity_entitlements"
                            },
                            {
                                "label": "Impersonate Identity",
                                "value": "impersonate_identities"
                            },
                            {
                                "label": "List and Search All Groups",
                                "value": "view_groups"
                            },
                            {
                                "label": "Manage Groups",
                                "value": "manage_groups"
                            },
                            {
                                "label": "Manage Systems",
                                "value": "manage_systems"
                            },
                            {
                                "label": "Manage Apis",
                                "value": "manage_apis"
                            },
                            {
                                "label": "Manage Entitlements",
                                "value": "manage_entitlements"
                            },
                            {
                                "label": "View Jobs",
                                "value": "view_jobs"
                            },
                            {
                                "label": "Manage Jobs",
                                "value": "manage_jobs"
                            },
                            {
                                "label": "Manage Systems Config",
                                "value": "manage_systems_config"
                            },
                            {
                                "label": "View Identity Logs",
                                "value": "view_logs"
                            },
                            {
                                "label": "Manage Logs",
                                "value": "manage_logs"
                            },
                            {
                                "label": "View Group Action Queue",
                                "value": "view_group_action_queue"
                            },
                            {
                                "label": "Manage Group Action Queue",
                                "value": "manage_group_action_queue"
                            },
                            {
                                "label": "View / Run Reports",
                                "value": "view_reports"
                            },
                            {
                                "label": "Manage Reports",
                                "value": "manage_reports"
                            }
                        ]
                    }
                ],
                "el":".identity-site-permissions",
                "data":{"permissions":data.permissions},
                "actions":[
                    {
                        "type": auth_user_perms.some(e=> {return e === "manage_identity_permissions"}) ?"save":"hidden",
                        "label":"Update Permissions","modifiers":"btn btn-primary"}
                ]}
            ).on('save',function(form_event) {
                ajax.put('/api/identities/'+identity_id+'/permissions',form_event.form.get(),function(data) {});
            });
            // end

        });
    } else {
        $('.identity-view').hide();
    }
}

ajax.get('/api/configuration',function(configuration) {
    ajax.get('/api/systems',function(systems) {
        var unique_ids_fields = {type: "fieldset",label:'Unique IDs',name: "ids",fields:_.find(configuration,{name:'identity_unique_ids'}).config};
        identity_form_attributes.push(unique_ids_fields);
        var identity_attributes_fields = {type: "fieldset",label:'Additional Attributes',name: "additional_attributes",fields:
            _.map(_.find(configuration,{name:'identity_attributes'}).config,function(item) {
                if (item.array == true) {item.array = {min:0,max:100}};
                return item;
            })
        };
        identity_form_attributes.push(identity_attributes_fields);
        search_identities_form = new gform(
            {"fields":[
                {name:'query',label:false,placeholder:'Search', pre:'<i class="fa fa-filter"></i>',help:"Search for name, account, or id"},
                {type:'output',name:'results',label:false}
            ],
            "el":".identity-search",
            "actions":[]
        }).on('change:query',function(){
            $('.identity-view').hide();
        })
        .on('change:query',_.debounce(function(e){
            ajax.get('/api/identities/search/'+this.toJSON().query,function(data) {
                var html = Ractive({template:identitylist_template,data:{identities:data}}).toHTML();
                search_identities_form.find('results').update({value:html});
            });
        },500))
    
        app.click('.list-group-item.identity', function(e) {
            manage_identity(e.currentTarget.dataset.id);
        });
        app.click('.identity-action',function(e) {
            identity_form.trigger(e.target.dataset.action);
        }) 
        app.click('.future-impact-btn',function(e) {
            identity_form.trigger('future_impact');
        })
        app.click('.future-impact-msg-btn',function(e) {
            mymodal.modal().trigger('close');
            identity_form.trigger('future_impact_msg');
        })
        app.click('.account-info-btn',function(e){
            $.ajax({
                url: '/api/identities/'+app.data.identity_id+'/accounts/'+e.target.dataset.id,
                success: function(account_data) {
                    var output = '';
                    var system = _.find(systems,{id:account_data.system_id})
                    var response_code = system.config.api.info.response_code;
                    var template = system.config.template;
                    var use_template = (template != null && template != '' && typeof account_data.info.content === 'object' && account_data.info.code == response_code);
                    if (use_template) {
                        output += Ractive({template:template,data:account_data.info.content}).toHTML();
                        output += '<div><a class="btn btn-primary" role="button" data-toggle="collapse" href="#rawresponse" style="margin:15px 0px;">Show/Hide Raw Response</a></div><div class="collapse" id="rawresponse">'
                    }
                    output += '<pre style="margin:0px;">'+JSON.stringify(account_data.info,null,2)+'</pre>';
                    if (use_template) {
                        output += '</div>'
                    }
                    mymodal.modal().set({output:output});
                },
                error: function(data){
                    console.log(data)
                    toastr.error(data.responseJSON.message)
                }
            })
        });
    
        if (typeof id !== 'undefined') {
            manage_identity(id);
        }    
    })
})
