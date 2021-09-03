dashboard_template = `
<h1>Welcome {{first_name}} {{last_name}}</h1>
<div class="row">
    <div class="col-sm-6">
        <div class="panel panel-default">
            <div class="panel-heading"><h3 class="panel-title">My Site Permissions</h3></div>
            <div class="panel-body">
                <ul>
                    {{#user_permissions}}
                        <li>{{.}}</li>
                    {{/user_permissions}}
                </ul>
                {{^user_permissions}}
                    <div class="alert alert-warning">No Site Level Permissions</div>
                {{/user_permissions}}
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading"><h3 class="panel-title">My Group Memberships</h3></div>
            <div class="panel-body">
                <ul>
                {{#pivot_groups}}
                    <li><a href="/admin/groups/{{id}}/members">{{name}}</a> ({{pivot.type}})</li>
                {{/pivot_groups}}
                </ul>
                {{^pivot_groups}}
                    <div class="alert alert-warning">No Group Memberships</div>
                {{/pivot_groups}}
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="panel panel-default">
            <div class="panel-heading"><h3 class="panel-title">My Module Permissions</h3></div>
            <div class="panel-body">
                <ul>
                    {{#owned_modules}}
                        <li><a href="/admin/modules/{{id}}/versions">{{name}}</a> (owner)</li>
                    {{/owned_modules}}
                    {{#pivot_module_permissions}}
                        <li><a href="/admin/modules/{{id}}/versions">{{name}}</a> ({{pivot.permission}})</li>
                    {{/pivot_module_permissions}}
                </ul>
            </div>
        <div>
    </div>
</div>
`;

ajax.get('/api/users/'+id,function(data) {
    $('#adminDataGrid').html(gform.m(dashboard_template,data));
});
