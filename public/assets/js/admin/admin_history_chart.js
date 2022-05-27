history_data = `
<div id="chart"></div>
`
toastr.info("Loading the data...")
ajax.get('/api/membership_logs/',function(data) {
    toastr.clear()
    var addedData = ['added'].concat(_.pluck(data.filter(e=>e.action=='add'),'num'))
    var removedData = ['deleted'].concat(_.pluck(data.filter(e=>e.action=='delete'),'num'))

    $('#adminDataGrid').html(history_data);
    var chart = c3.generate({
        bindto: '#chart',
        data: {
            type: 'bar',
            columns:[addedData, removedData ]
            },

        bar: {
            width: {
                ratio: 0.1 // this makes bar width 50% of length between ticks
            }
        }
    });
});