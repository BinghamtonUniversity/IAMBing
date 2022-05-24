$.fn.modal.prototype.constructor.Constructor.DEFAULTS.backdrop = 'static';
$.fn.modal.prototype.constructor.Constructor.DEFAULTS.keyboard =  false;

window.app = {
    data:{current_year:new Date().getFullYear()},
    update:{},
    forms:{},
}
window.templates = {
    main: ''
}

var ractive = Ractive({
    target: '#main_target',
    template: window.templates.main,
    partials: window.templates,
    data: app.data
});

app.update = function(newdata) {
    if (typeof newdata !== 'undefined') {
        for (new_data_key in newdata) {
            app.data[new_data_key] = newdata[new_data_key]
        }
    } 
    ractive.set(app.data)
    for (data_key in app.data) {
        gform.collections.update(data_key, app.data[data_key])
    }
};

app.get = function(method,data,callback_success,error_callback) {
    $.ajax({
        url:'handler?action='+method,
        success:function(data){callback_success(data)},
        error:function(data){
            if (typeof error_callback !== 'undefined') {
                error_callback(data)
            }
        }
    })
}

app.post = function(method, data, success_callback, error_callback) {
    toastr.info("Processing... Please Wait!")
    if (typeof data.username !== 'undefined') {
        data.username = data.username.trim();
    }
    $.ajax({
        dataType: "json",
        method: "POST",
        url: 'handler?action='+method,
        data: data,
        success: function(data,status,xhr) {
            toastr.remove()
            if (typeof data.error === 'string') {
                toastr.remove()
                toastr.error(data.error);
                if (typeof error_callback !== 'undefined') {
                    error_callback(data)
                }
            } else if (_.has(data,'error.message') && typeof data.error.message === 'string') {
                toastr.remove()
                toastr.error(data.error.message);
                if (typeof error_callback !== 'undefined') {
                    error_callback(data)
                }
            } else if (typeof data.success === 'undefined' || (typeof data.success !== undefined && data.success==false)) {
                toastr.remove()
                toastr.error('An Unexpected Error Occurred.  Please try again.');
                if (typeof error_callback !== 'undefined') {
                    error_callback(data)
                }
            }
            if (typeof data.success !== undefined && data.success == true) {
                success_callback(data)
            }
        },
        error: function(xhr,status) {
            if (xhr.status == 401) {
                toastr.error("You are not authenticated.  Redirecting to login.")
                window.location = '/';
            }
            toastr.remove();
            toastr.error('An Unexpected Error Occurred.  Please try again.')
            if (typeof error_callback !== 'undefined') {
                error_callback(data.responseJSON)
            }
        }
    });
}


app.fetch = function(callback) {
    app.get('config.php',{},function(resp_data){
        app.update(resp_data);
        callback();
    })
}

app.findForm = function(form_name) {
    if (typeof window.forms[form_name] !== 'undefined') {
        return window.forms[form_name]
    } else {
        return null;
    }
}

app.form = function(form_name,target) {
    if (_.has(app.forms,form_name)) {
        return app.forms[form_name];
    }
    form_definition = app.findForm(form_name);
    if (form_definition !== null) {
        if (typeof target !== 'undefined') {
            app.forms[form_name] = new gform(form_definition,target) 
            return app.forms[form_name];  
        } else {
            app.forms[form_name] = new gform(form_definition)
            return app.forms[form_name];
        }
    } else {
        return null;
    }
}

app.render = function(template_name, data) {
    var local_ractive = Ractive({
        template: window.templates[template_name],
        partials: window.templates,
        data: data
    });
    return local_ractive.toHTML();
}

toastr.options = {
    "positionClass": "toast-bottom-right",
    "timeOut": "10000",
}

app.alert = function(config) {
    if (typeof config === 'string') {
        toastr.info(config)
    } else {
        if (typeof config.status === 'undefined') {
            config.status = 'success'
        }
        if (typeof config.title === 'undefined') {
            config.title = ''
        }
        if (typeof config.content === 'undefined') {
            config.content = ''
        }
        toastr[config.status](config.title, config.content)
    }
}

$('#app-modal').on('hide.bs.modal', function (e) {
    app.data._modal.content = '';
    app.update();
})
app.modal = function(config,callback) {
    if (typeof config === 'string') {
        app.data._modal.title = '';
        app.data._modal.content = config;
    } else {
        app.data._modal = config;
        if (typeof app.data._modal.title === 'undefined') {
            app.data._modal.title = '';
        }
        if (typeof app.data._modal.content === 'undefined') {
            app.data._modal.content = '';
        }
        app.data._modal.close = true;
    }
    app.update();
    $('#app-modal').modal('show')
    $('#app-modal').on('shown.bs.modal', function () {
        if (typeof callback !== 'undefined') {
            callback();
        }
    })
}
$(document).on('hidden.bs.modal', '#app-modal', function (e) {
    app.data._modal.content = '';
    app.data._modal.title = '';
    app.update();
})

app.click = function(selector, callback) {
    $(document).on("click", selector, callback);
    $(document).on("keypress", selector, function(event) {
        if (event.keyCode === 13) {
            callback(event);
        }
    });
}

$(function () {
    $('body').tooltip({
        selector: '[data-toggle=tooltip]'
    });
})

app.copy = function(selector) {
    var range = document.createRange();
    range.selectNode(document.querySelector(selector));
    window.getSelection().removeAllRanges(); 
    window.getSelection().addRange(range); 
    document.execCommand("copy");
    window.getSelection().removeAllRanges();
    app.alert("Copied to Clipboard")
}