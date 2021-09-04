gform.options = {autoFocus:false};
user_form_attributes = [
    {type:"hidden", name:"id"},
    {type:"checkbox", name:"active", label:"Active", value:true},
    {type:"text", name:"unique_id", label:"Unique ID", required:true},
    {type:"text", name:"first_name", label:"First Name"},
    {type:"text", name:"last_name", label:"Last Name"},
    {type:"email", name:"email", label:"Email", required:true}
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
    <div class="col-sm-6">
        <div class="panel panel-default">
            <div class="panel-heading"><h3 class="panel-title">User</h3></div>
            <div class="panel-body user-edit"></div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="panel panel-default">
            <div class="panel-heading"><h3 class="panel-title">Accounts</h3></div>
            <div class="panel-body user-accounts"></div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading"><h3 class="panel-title">Groups</h3></div>
            <div class="panel-body user-groups"></div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading"><h3 class="panel-title">Permissions</h3></div>
            <div class="panel-body user-site-permissions"></div>
        </div>
    </div>
    </div>
</div>
`);

user_groups_template = `
<ul>
    {{#pivot_groups}}
        <li><a href="/admin/groups/{{id}}/members">{{name}}</a></li>
    {{/pivot_groups}}
</ul>
{{^pivot_groups}}
    <div class="alert alert-warning">No Group Memberships</div>
{{/pivot_groups}}
`;

user_accounts_template = `
<ul>
    {{#systems}}
        <li>{{pivot.username}} ({{name}})</li>
    {{/systems}}
</ul>
{{^systems}}
    <div class="alert alert-warning">No Accounts</div>
{{/systems}}
<a class="btn btn-primary" href="/admin/users/{{id}}/accounts">Manage Accounts</a>
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
                form_event.form.trigger('close');
            });
        }
    });
})

new gform(
    {"fields":[
        {
            "type": "user",
            "label": "Search Existing Users",
            "name": "user",
        }    
    ],
    "el":".user-search",
    "actions":[
        {"type":"save","label":"Submit","modifiers":"btn btn-primary"}
    ]
}
).on('change',function(form_event) {
    form_data = form_event.form.get();
    if (form_data.user == null || form_data.user == '') {
        $('.user-view').hide();
    }
}).on('save',function(form_event) {
    form_data = form_event.form.get();
    if (form_data.user != null && form_data.user != '') {
        user_id = form_data.user;
        ajax.get('/api/users/'+form_data.user,function(data) {
            $('.user-view').show();
            // Show Groups
            $('.user-groups').html(gform.m(user_groups_template,data));
            $('.user-accounts').html(gform.m(user_accounts_template,data));
            // Edit User
            new gform(
                {"fields":user_form_attributes,
                "el":".user-edit",
                "data":data,
                "actions":[
                    {"type":"save","label":"Update User","modifiers":"btn btn-primary"},
                    {"type":"button","label":"Delete User","action":"delete","modifiers":"btn btn-danger"},
                    {"type":"button","label":"Merge Into","action":"merge_user","modifiers":"btn btn-danger"},
                    {"type":"button","label":"Login","action":"login","modifiers":"btn btn-warning"}
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
});
