<!DOCTYPE html>
<html>
<head>
    <title>PsicoManager API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@3.25.0/swagger-ui.css" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin:0;
            background: #fafafa;
        }
        .topbar {
            background-color: #2c3e50;
        }
        .topbar-wrapper .link::after {
            content: "PsicoManager API v1.0.0";
        }
        .swagger-ui .info .title {
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@3.25.0/swagger-ui-bundle.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "http://localhost:8888/api/docs/json",
                dom_id: "#swagger-ui",
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.presets.standalone
                ],
                layout: "BaseLayout",
                requestInterceptor: function(request) {
                    console.log('Making request:', request);
                    return request;
                }
            });
        };
    </script>
</body>
</html>