dashboard_template = `
<h1>Welcome {{first_name}} {{last_name}}</h1>
<div class="row">
    <div class="col-sm-6">
        <div class="panel panel-default">
            <div class="panel-heading"><h3 class="panel-title">My Groups</h3></div>
            <div class="panel-body">
                {{#groups}}
                    <li>{{name}}</li>
                {{/groups}}
                {{^groups}}
                    <div class="alert alert-warning">No Group Memberships</div>
                {{/groups}}
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading"><h3 class="panel-title">My Accounts</h3></div>
            <div class="panel-body">
                <ul>
                    {{#systems}}
                        <li>{{pivot.username}} ({{name}})</li>
                    {{/systems}}
                </ul>
                {{^systems}}
                    <div class="alert alert-warning">No Accounts</div>
                {{/systems}}
            </div>
        </div>
    </div>
</div>
`;

ajax.get('/api/users/'+id,function(data) {
    $('#adminDataGrid').html(gform.m(dashboard_template,data));
});
