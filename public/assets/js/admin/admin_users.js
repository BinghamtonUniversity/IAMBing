gform.options = {autoFocus:false};
user_form_attributes = [
    {type:"switch", name:"active", label:"Active", value:true, columns:6, options:[{label:'Inactive',value:false},{label:'Active',value:true}]},
    {type:"switch", name:"sponsored", label:"Sponsored", value:false, columns:6, options:[{label:'Default',value:false},{label:'Sponsored',value:true}]},
    {type:"text", name:"id", label: 'IAMBing ID', edit:false},
    {type:"text", name:"first_name", label:"First Name", required:true},
    {type:"text", name:"last_name", label:"Last Name", required:true},
    {type:"text", name:"default_username", label:"Default Username", required:false, help:'Leave blank to define automatically'},
    {type:"text", name:"default_email", label:"Default Email Address", required:false},
    {type:"user", name:"sponsor_user_id",required:false, label:"Sponsor",show:[{type:'matches',name:'sponsored',value:true}]},
];


$('#adminDataGrid').html(`
<div class="row">
    <div class="col-sm-3 actions">
        <div class="row">
            <div class="col-sm-12 user-search"></div>
        </div>
        <hr>
        <div class="row">
            <div class="col-sm-12">
                <div class="btn btn-success user-new">Create New User</div><br><br>
            </div>
        </div>
    </div>
    <div class="col-sm-9 user-view" style="display:none;">
        <div class="row">
            <div class="col-sm-6">
                <div class="panel panel-default">
                    <div class="panel-heading"><h3 class="panel-title">User</h3></div>
                    <div class="panel-body user-edit"></div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="userinfo-column"></div>
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

userlist_template = `
{{#users.length}}
    Select User to View
{{/users.length}}
<hr style="border:solid 1px #333">
{{^users.length}}No results{{/users.length}}
<div class="list-group">
    {{#users}}
        <a href="javascript:void(0);" class="list-group-item user" data-id="{{id}}">
            <div class="badge pull-right">{{default_username}}</div>
            {{first_name}} {{last_name}}
        </a>
    {{/users}}
</div>
`;

userinfo_column_template = `
<div class="panel panel-default">
<div class="panel-heading"><h3 class="panel-title">
    <a class="btn btn-primary btn-xs pull-right" href="/users/{{id}}/accounts">Override Accounts</a>
    Systems / Accounts
</h3></div>
<div class="panel-body user-accounts">{{>user_accounts_template}}</div>
</div>
<div class="panel panel-default">
<div class="panel-heading"><h3 class="panel-title">Affiliations</h3></div>
<div class="panel-body user-affiliations">{{>user_affiliations_template}}</div>
</div>
<div class="panel panel-default">
<div class="panel-heading"><h3 class="panel-title">
    <a class="btn btn-primary btn-xs pull-right" href="/users/{{id}}/entitlements">Override Entitlements</a>
    Entitlements
</h3></div>
<div class="panel-body user-entitlements">{{>user_entitlements_template}}</div>
</div>
<div class="panel panel-default">
<div class="panel-heading"><h3 class="panel-title">
    <a class="btn btn-primary btn-xs pull-right" href="/users/{{id}}/groups">Manage Groups</a> 
    Groups
</h3></div>
<div class="panel-body user-groups">{{>user_groups_template}}</div>
</div>

<div class="panel panel-default">
<div class="panel-heading"><h3 class="panel-title">Sponsored Users</h3></div>
<div class="panel-body user-groups">{{>sponsored_users_template}}</div>
</div>

<div class="panel panel-default">
<div class="panel-heading"><h3 class="panel-title">Permissions</h3></div>
<div class="panel-body user-site-permissions"></div>
</div>
`;

user_groups_template = `
<div style="font-size:20px;">
{{#groups}}
    <a class="label label-default" href="/groups/{{id}}/members">{{name}}</a>
{{/groups}}
</div>
{{^groups}}
    <div class="alert alert-warning">No Group Memberships</div>
{{/groups}}
`;

sponsored_users_template = `
<div style="font-size:20px;">
{{#sponsored_users}}
    <a href="/users/{{id}}" class="label label-default">            
        <div class="label label-primary pull-right">{{default_username}}</div>
        {{first_name}} {{last_name}}
    </a>
{{/sponsored_users}}
</div>
{{^sponsored_users}}
    <div class="alert alert-warning">No Sponsored Users</div>
{{/sponsored_users}}
`;


user_affiliations_template = `
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

user_entitlements_template = `
<h5>Enforced</h5>
<div  class="well well-sm"><i class="fa fa-info-circle"></i> These are the entitlements as they are currently enforced, taking into account any manual overrides which may deviate from the default entitlement calculations</div>
<div style="font-size:20px;">
{{#user_entitlements}}
    {{#pivot.override}}
        {{#pivot.type === 'remove'}}
            <div class="label label-danger">{{name}}</div>
            <div style="text-align:center;font-size:12px;">(Manually Removed Until: {{pivot.override_expiration}})</div>
        {{/}}
        {{#pivot.type === 'add'}}
            <div class="label label-success">{{name}}</div>
            <div style="text-align:center;font-size:12px;">(Manually Added Until: {{pivot.override_expiration}})</div>
        {{/}}
    {{/}}
    {{^pivot.override}}
        <div class="label label-default">{{name}}</div>
    {{/}}
{{/user_entitlements}}
</div>
{{^user_entitlements}}
    <div class="alert alert-warning">No Entitlements</div>
{{/user_entitlements}}
<hr>
<h5>Calculated</h5>
<div class="well well-sm"><i class="fa fa-info-circle"></i> These are the entitlements which are automatically calculated based on group memberships</div>
<div style="font-size:15px;">
{{#calculated_entitlements}}
    <div class="label label-default" style="display:inline-block;margin:0px 5px 5px 0px;">{{name}}</div>
{{/calculated_entitlements}}
</div>
`;


user_accounts_template = `
<div  class="well well-sm"><i class="fa fa-info-circle"></i> These are the accounts which are currently assigned to this user, which facilitate their entitlements.</div>
<div style="font-size:20px;">
    {{#systems}}
        {{#if pivot.status === 'active'}}
            <div class="label label-default">{{name}} / {{pivot.account_id}}</div>
        {{/if}}
    {{/systems}}
    {{#systems}}
        {{#if pivot.status === 'disabled'}}
            <div class="label label-danger">{{name}} / {{pivot.account_id}}</div>
        {{/if}}
    {{/systems}}
</div>
{{^systems}}
    <div class="alert alert-warning">No Accounts</div>
{{/systems}}
`;

// Create New User
$('.user-new').on('click',function() {
    new gform(
        {"fields":user_form_attributes,
        "title":"Create New User",
        "actions":[
            {"type":"save"}
        ]}
    ).modal().on('save',function(form_event) {
        if(form_event.form.validate())
        {
            ajax.post('/api/users', form_event.form.get(), function (data) {
                manage_user(data.id);
                form_event.form.trigger('close');
            });
        }
    });
})

var manage_user = function(user_id) {
    if (user_id != null && user_id != '') {
        ajax.get('/api/users/'+user_id,function(data) {
            window.history.pushState({},'','/users/'+user_id);
            $('.user-view').show();

            $('.userinfo-column').html(Ractive({
                template:userinfo_column_template,
                partials: {
                    user_groups_template:user_groups_template,
                    user_entitlements_template:user_entitlements_template,
                    user_affiliations_template:user_affiliations_template,
                    user_accounts_template:user_accounts_template,
                    sponsored_users_template,
                },
                data:data
            }).toHTML());

            // $('.user-groups').html(gform.m(user_groups_template,data));
            // $('.user-affiliations').html(Ractive({template:user_affiliations_template,data:data}).toHTML());
            // $('.user-accounts').html(gform.m(user_accounts_template,data));
            // $('.user-entitlements').html(Ractive({template:user_entitlements_template,data:data}).toHTML());

            // Edit User
            new gform(
                {"fields":user_form_attributes,
                "el":".user-edit",
                "data":data,
                "actions":[
                    {"type":"save","label":"Update User","modifiers":"btn btn-primary"},
                    {"type":"button","label":"Delete User","action":"delete","modifiers":"btn btn-danger"},
                    {"type":"button","label":"Merge Into","action":"merge_user","modifiers":"btn btn-danger"},
                    {"type":"button","label":"Login","action":"login","modifiers":"btn btn-warning"},
                    {"type":"button","label":"Recalculate","action":"recalculate","modifiers":"btn btn-warning"}
                ]}
            ).on('delete',function(form_event) {
                form_data = form_event.form.get();
                if (confirm('Are you super sure you want to do this?  This action cannot be undone!')){
                    ajax.delete('/api/users/'+form_data.id,{},function(data) {
                        $('.user-view').hide();
                    });
                }
            }).on('merge_user',function(form_event) {
                form_data = form_event.form.get();
                source_user = form_data.id;
                new gform(
                    {"fields":[{
                        "type": "user",
                        "label": "Target User",
                        "name": "target_user",
                        "required":true,          
                    },{type:"checkbox", name:"delete", label:"Delete Source User", value:false,help:"By checking this box, the `source` user will be irretrievably deleted from BComply."},
                    {type:"output",parse:false,value:'<div class="alert alert-danger">This action will migrate/transfer all assignments from the source user to the specified target user.  This is a permanent and "undoable" action.</div>'}],
                    "title":"Merge Into",
                    "actions":[
                        {"type":"cancel"},
                        {"type":"button","label":"Commit Merge","action":"save","modifiers":"btn btn-danger"},
                    ]}
                ).modal().on('save',function(merge_form_event) {
                    var merge_form_data = merge_form_event.form.get();
                    if(form_event.form.validate() && merge_form_data.target_user !== '')
                    {
                        if (confirm("Are you sure you want to merge these users?  This action cannot be undone!")) {
                            ajax.put('/api/users/'+source_user+'/merge_into/'+merge_form_data.target_user, {delete:merge_form_data.delete}, function (data) {
                                merge_form_event.form.trigger('close');
                                if (_.has(data,'errors')) {
                                    toastr.error('One or more errors occurred.')
                                    console.log(data.errors);
                                    window.alert(data.errors.join("\n"))
                                } else {
                                    toastr.success('User Merge Successful!');
                                }
                            });
                        }
                    }
                }).on('cancel',function(merge_form_event) {
                    merge_form_event.form.trigger('close');
                });            
            }).on('save',function(form_event) {
                if(form_event.form.validate())
                {
                    form_data = form_event.form.get();
                    ajax.put('/api/users/' + form_data.id, form_data, function (data) {
                    });
                }
            }).on('login',function(form_event) {
                form_data = form_event.form.get();
                ajax.post('/api/login/'+form_data.id,{},function(data) {
                    window.location = '/';
                });
            }).on('recalculate',function(form_event) {
                form_data = form_event.form.get();
                ajax.get('/api/users/'+form_data.id+'/recalculate',function(data) {
                    manage_user(data.id);
                });
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
                        "options": [
                            'manage_user_permissions',
                            'manage_groups',
                            'manage_users',
                            'manage_systems',
                            'manage_entitlements'
                        ]
                    }    
                ],
                "el":".user-site-permissions",
                "data":{"permissions":data.permissions},
                "actions":[
                    {"type":"save","label":"Update Permissions","modifiers":"btn btn-primary"}
                ]}
            ).on('save',function(form_event) {
                ajax.put('/api/users/'+user_id+'/permissions',form_event.form.get(),function(data) {});
            });
            // end

        });
    } else {
        $('.user-view').hide();
    }
}

ajax.get('/api/configuration/',function(data) {
    var unique_ids_fields = {type: "fieldset",label:'Unique IDs',name: "ids",fields:_.find(data,{name:'user_unique_ids'}).config};
    user_form_attributes.push(unique_ids_fields);
    var user_attributes_fields = {type: "fieldset",label:'Attributes',name: "attributes",fields:_.find(data,{name:'user_attributes'}).config};
    user_form_attributes.push(user_attributes_fields);
    new gform(
        {"fields":[
			{name:'query',label:false,placeholder:'Search', pre:'<i class="fa fa-filter"></i>',help:"Search for name, username, or unique id<hr>"},
			{type:'output',name:'results',label:false}
        ],
        "el":".user-search",
        "actions":[]
    }).on('change:query',function(){
        $('.user-view').hide();
    })
    .on('change:query',_.debounce(function(e){
        $.ajax({
            url: '/api/users/search/'+this.toJSON().query,
            success: function(data) {
                var html = Ractive({template:userlist_template,data:{users:data}}).toHTML();
                e.form.find('results').update({value:html});
            }.bind(e)
        })
    },500))

    $('body').on('click','.list-group-item.user', function(e){
		manage_user(e.currentTarget.dataset.id);
    });

    if (typeof id !== 'undefined') {
        manage_user(id);
    }

})
