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
    {type:"text", name:"default_email", label:"Default Email Address", required:false},
    {type:"identity", name:"sponsor_identity_id",required:false, label:"Sponsor",show:[{type:'matches',name:'sponsored',value:true}]},
];

$('#adminDataGrid').html(`
<div class="row">
    <div class="col-sm-3 actions">
        <div class="row">
            <div class="col-sm-12 identity-search"></div>
        </div>
        <hr>
        <div class="row">
            <div class="col-sm-12">
                <div class="btn btn-success identity-new">Create New Identity</div><br><br>
            </div>
        </div>
    </div>
    <div class="col-sm-9 identity-view" style="display:none;">
        <div class="row">
            <div class="col-sm-6">
                <div class="panel panel-default">
                    <div class="panel-heading"><h3 class="panel-title">Identity</h3></div>
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
`);

identitylist_template = `
{{#identities.length}}
    Select Identity to View
{{/identities.length}}
<hr style="border:solid 1px #333">
{{^identities.length}}No results{{/identities.length}}
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
<div class="panel panel-default">
<div class="panel-heading"><h3 class="panel-title">
    {{#auth_user_perms}}
        {{#if . == "override_identity_accounts"}}
            <a class="btn btn-primary btn-xs pull-right" href="/identities/{{id}}/accounts">Override Accounts</a>
        {{/if}}
    {{/auth_user_perms}}
    Systems / Accounts
</h3></div>
<div class="panel-body identity-accounts">{{>identity_accounts_template}}</div>
</div>
<div class="panel panel-default">
<div class="panel-heading"><h3 class="panel-title">Affiliations</h3></div>
<div class="panel-body identity-affiliations">{{>identity_affiliations_template}}</div>
</div>
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
<div class="panel panel-default">
<div class="panel-heading"><h3 class="panel-title">
    {{#auth_user_perms}}
        {{#if . == "manage_groups"}}
            <a class="btn btn-primary btn-xs pull-right" href="/identities/{{id}}/groups">Manage Groups</a>
        {{/if}}
    {{/auth_user_perms}}
    Groups
</h3></div>
<div class="panel-body identity-groups">{{>identity_groups_template}}</div>
</div>

<div class="panel panel-default">
<div class="panel-heading"><h3 class="panel-title">Sponsored Identities</h3></div>
<div class="panel-body identity-groups">{{>sponsored_identities_template}}</div>
</div>

<div class="panel panel-default">
<div class="panel-heading"><h3 class="panel-title">Permissions</h3></div>
<div class="panel-body identity-site-permissions"></div>
</div>
`;

identity_groups_template = `
<div style="font-size:20px;">
{{#groups}}
    <a class="label label-default" href="/groups/{{id}}/members">{{name}}</a>
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

identity_entitlements_template = `
<h5>Enforced</h5>
<div  class="well well-sm"><i class="fa fa-info-circle"></i> These are the entitlements as they are currently enforced, taking into account any manual overrides which may deviate from the default entitlement calculations</div>
<div style="font-size:20px;">
{{#identity_entitlements}}
    {{#pivot.override}}
        {{#pivot.type === 'remove'}}
            <div class="label label-danger">
                {{name}}
                <div class="tinytext">(Manually Removed Until: {{pivot.override_expiration}})</div>
            </div>
        {{/}}
        {{#pivot.type === 'add'}}
            <div class="label label-success">
                {{name}}
                <div class="tinytext">
                    {{#pivot.expire}}(Manually Added Until: {{pivot.expiration_date}}){{/}}
                    {{^pivot.expire}}(Manually Added: No Expiration){{/}}
                </div>
            </div>
        {{/}}
    {{/}}
    {{^pivot.override}}
        <div class="label label-default">{{name}}</div>
    {{/}}
{{/identity_entitlements}}
</div>
{{^identity_entitlements}}
    <div class="alert alert-warning">No Entitlements</div>
{{/identity_entitlements}}
<hr>
<h5>Calculated</h5>
<div class="well well-sm"><i class="fa fa-info-circle"></i> These are the entitlements which are automatically calculated based on group memberships</div>
<div style="font-size:15px;">
{{#calculated_entitlements}}
    <div class="label label-default" style="display:inline-block;margin:0px 5px 5px 0px;">{{name}}</div>
{{/calculated_entitlements}}
</div>
`;


identity_accounts_template = `
<div  class="well well-sm"><i class="fa fa-info-circle"></i> These are the accounts which are currently assigned to this identity, which facilitate their entitlements.</div>
<div style="font-size:20px;">
    {{#systems}}
        {{#if pivot.override === 1}}
            <div class="label {{#pivot.status === 'active'}}label-success{{/}}{{#pivot.status === 'disabled'}}label-danger{{/}}">
                <i class="fa fa-info-circle pull-right account-info-btn" data-id="{{pivot.id}}" style="cursor:pointer;"></i>
                {{name}} / {{pivot.account_id}}
                {{#pivot.status === 'active'}}<div class="tinytext">(Enabled via Manual Override)</div>{{/}}
                {{#pivot.status === 'disabled'}}<div class="tinytext">(Disabled via Manual Override)</div>{{/}}
            </div>
        {{else}}
            <div class="label {{#pivot.status === 'active'}}label-default{{/}}{{#pivot.status === 'disabled'}}label-warning{{/}}">
                <i class="fa fa-info-circle pull-right account-info-btn" data-id="{{pivot.id}}" style="cursor:pointer;"></i>
                {{name}} / {{pivot.account_id}}
                {{#pivot.status === 'disabled'}}<div class="tinytext">(Automatically Disabled)</div>{{/}}
            </div>
        {{/if}}
    {{/systems}}
</div>
{{^systems}}
    <div class="alert alert-warning">No Accounts</div>
{{/systems}}
`;

// Create New Identity
$('.identity-new').on('click',function() {
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
                    identity_affiliations_template:identity_affiliations_template,
                    identity_accounts_template:identity_accounts_template,
                    sponsored_identities_template,
                },
                data:data
            }).toHTML());

            // $('.identity-groups').html(gform.m(identity_groups_template,data));
            // $('.identity-affiliations').html(Ractive({template:identity_affiliations_template,data:data}).toHTML());
            // $('.identity-accounts').html(gform.m(identity_accounts_template,data));
            // $('.identity-entitlements').html(Ractive({template:identity_entitlements_template,data:data}).toHTML());
            // console.log(data)
            // Edit Identity
            new gform(
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
                if (confirm('Are you super sure you want to do this?  This action cannot be undone!')){
                    ajax.delete('/api/identities/'+form_data.id,{},function(data) {
                        $('.identity-view').hide();
                    });
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
                }).on('cancel',function(merge_form_event) {
                    merge_form_event.form.trigger('close');
                });
            }).on('save',function(form_event) {
                if(form_event.form.validate())
                {

                    form_data = form_event.form.get();
                    ajax.put('/api/identities/' + form_data.id, form_data, function (data) {
                    });
                }
            }).on('login',function(form_event) {
                form_data = form_event.form.get();
                ajax.post('/api/login/'+form_data.id,{},function(data) {
                    window.location = '/';
                });
            }).on('recalculate',function(form_event) {
                form_data = form_event.form.get();
                ajax.get('/api/identities/'+identity_id+'/recalculate',function(data) {
                    manage_identity(data.id);
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
                                "label": "Override Identity Accounts",
                                "value": "override_identity_accounts"
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
                                "label": "View Group Confirmation Queue",
                                "value": "view_group_confirmation_queue"
                            },
                            {
                                "label": "Manage Group Confirmation Queue",
                                "value": "manage_group_confirmation_queue"
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

        var mymodal = new gform(
            {"fields":[
                {name:'output',value:'',type:'output',label:''}
            ],
            "title":"Account Info",
            "actions":[]}
        );
        $('body').on('click','.account-info-btn',function(e){
            $.ajax({
                url: '/api/identities/'+identity_id+'/accounts/'+e.target.dataset.id,
                success: function(data) {
                    mymodal.modal().set({output:'<pre>'+JSON.stringify(data.info,null,2)+'</pre>'});
                },
                error: function(data){
                    console.log(data)
                    toastr.error(data.responseJSON.message)
                }
            })
        });
    } else {
        $('.identity-view').hide();
    }
}

ajax.get('/api/configuration/',function(data) {
    var unique_ids_fields = {type: "fieldset",label:'Unique IDs',name: "ids",fields:_.find(data,{name:'identity_unique_ids'}).config};
    identity_form_attributes.push(unique_ids_fields);
    var identity_attributes_fields = {type: "fieldset",label:'Attributes',name: "attributes",fields:_.find(data,{name:'identity_attributes'}).config};
    identity_form_attributes.push(identity_attributes_fields);
    new gform(
        {"fields":[
			{name:'query',label:false,placeholder:'Search', pre:'<i class="fa fa-filter"></i>',help:"Search for name, username, or unique id<hr>"},
			{type:'output',name:'results',label:false}
        ],
        "el":".identity-search",
        "actions":[]
    }).on('change:query',function(){
        $('.identity-view').hide();
    })
    .on('change:query',_.debounce(function(e){
        $.ajax({
            url: '/api/identities/search/'+this.toJSON().query,
            success: function(data) {
                var html = Ractive({template:identitylist_template,data:{identities:data}}).toHTML();
                e.form.find('results').update({value:html});
            }.bind(e)
        })
    },500))

    $('body').on('click','.list-group-item.identity', function(e){
		manage_identity(e.currentTarget.dataset.id);
    });

    if (typeof id !== 'undefined') {
        manage_identity(id);
    }
})
