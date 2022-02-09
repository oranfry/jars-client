(function(){
    window.blends_api = {
        login: function(username, password) {
            $.ajax('/api/auth/login', {
                method: 'post',
                contentType: false,
                processData: false,
                data: JSON.stringify({username: username, password: password}),
                success: function(data) {
                    if (typeof data.token != 'undefined') {
                        setCookie('token', data.token);
                        window.location.reload();
                    } else {
                        alert(data.error || 'Unknown error');
                    }
                },
                error: function(data){
                    alert(data.responseJSON && data.responseJSON.error || 'Unknown error');
                }
            });
        },
        updateBlend: function(blend, query, data, success) {
            $.ajax('/api/blend/' + blend + '/update?' + query, {
                method: 'post',
                contentType: false,
                processData: false,
                data: JSON.stringify(data),
                beforeSend: function(request) {
                    request.setRequestHeader("X-Auth", getCookie('token'));
                },
                success: success,
                error: function(data){
                    alert(data.responseJSON && data.responseJSON.error || 'Unknown error');
                }
            });
        },
        linetypeAdd: function(linetype, repeater, range_from, range_to, data){
            $.ajax('/api/' + linetype + '/add?repeater=' + repeater + '&from=' + range_from + '&to=' + range_to, {
                method: 'post',
                contentType: false,
                processData: false,
                data: JSON.stringify(data),
                beforeSend: function(request) {
                    request.setRequestHeader("X-Auth", getCookie('token'));
                },
                success: function(data) {
                    window.location.reload();
                },
                error: function(data){
                    alert(data.responseJSON && data.responseJSON.error || 'Unknown error');
                }
            });
        },
        blendDelete: function(blend, query, success){
            $.ajax('/api/blend/' + blend + '?' + query, {
                method: 'delete',
                contentType: false,
                processData: false,
                beforeSend: function(request) {
                    request.setRequestHeader("X-Auth", getCookie('token'));
                },
                success: success,
                error: function(data){
                    alert(data.responseJSON.error);
                }
            });
        },
        blendPrint: function(blend, query) {
            $.ajax('/api/blend/' + blend + '/print?' + query, {
                method: 'post',
                contentType: false,
                processData: false,
                beforeSend: function(request) {
                    request.setRequestHeader("X-Auth", getCookie('token'));
                },
                error: function(data){
                    alert(data.responseJSON.error);
                }
            });
        },
        linePrint: function(linetype, id) {
            $.ajax('/api/' + linetype + '/print?id=' + id, {
                method: 'post',
                contentType: false,
                processData: false,
                beforeSend: function(request) {
                    request.setRequestHeader("X-Auth", getCookie('token'));
                },
                success: function(data) {
                    $('#output').html(data.messages.join(', '));
                }
            });
        },
        lineDelete: function(linetype, id) {
            $.ajax('/api/' + linetype + '/' + id, {
                method: 'delete',
                contentType: false,
                processData: false,
                beforeSend: function(request) {
                    request.setRequestHeader("X-Auth", getCookie('token'));
                },
                success: function() {
                    window.location.reload();
                },
                error: function(data){
                    alert(data.responseJSON.error);
                }
            });
        },
        lineUnlink: function(linetype, id, parent) {
            $.ajax('/api/' + linetype + '/' + id + '/unlink/' + parent, {
                method: 'post',
                contentType: false,
                processData: false,
                beforeSend: function(request) {
                    request.setRequestHeader("X-Auth", getCookie('token'));
                },
                success: function() {
                    window.location.reload();
                },
                error: function(data){
                    alert(data.responseJSON.error);
                }
            });
        },
        lineSave: function(lines, success) {
            $.ajax('/api', {
                method: 'post',
                contentType: false,
                processData: false,
                beforeSend: function(request) {
                    request.setRequestHeader("X-Auth", getCookie('token'));
                },
                data: JSON.stringify(lines),
                success: success,
                error: function(data){
                    alert(data.responseJSON.error);
                }
            });
        },
        lineGet: function(linetype, id, success) {
            $.ajax('/api/' + linetype + '/' + id, {
                method: 'get',
                contentType: false,
                processData: false,
                beforeSend: function(request) {
                    request.setRequestHeader("X-Auth", getCookie('token'));
                },
                success: success,
                error: function(data){
                    alert(data.responseJSON.error);
                }
            });
        },
        logout: function() {
            $.ajax('/api/auth/logout', {
                method: 'post',
                contentType: false,
                processData: false,
                beforeSend: function(request) {
                    request.setRequestHeader("X-Auth", getCookie('token'));
                },
                success: function(data) {
                    deleteCookie('token');
                    window.location.href = '/';
                },
                error: function(data){
                    alert(data.responseJSON && data.responseJSON.error || 'Unknown error');
                }
            });
        }
    };
})();
