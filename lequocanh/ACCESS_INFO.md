# Application Access Information

## Cloudflare Tunnel Access
Your application is configured to work with Cloudflare Tunnel. To access the application:

**URL:** https://tennis-manhattan-mothers-wrapped.trycloudflare.com

## Important Notes

1. **Do not access via localhost:** The application is configured to redirect to the Cloudflare tunnel when accessed via localhost.

2. **Cloudflare Tunnel Setup:** Make sure your Cloudflare tunnel is running with the command:
   ```
   .\cloudflared.exe --config cloudflared-config.yml tunnel --url http://localhost:80
   ```

3. **Environment Configuration:** The application will use the tunnel URL as the base URL when accessed from the tunnel domain.

## Troubleshooting

If you're still experiencing connection issues:

1. Verify that the Cloudflare tunnel is running properly
2. Check that your `cloudflared-config.yml` file is properly configured
3. Ensure that your local server (on port 80) is running and accessible
4. Try accessing the tunnel URL directly in your browser

## Configuration Override

If you need to access the application locally for development, you can temporarily set these values in your `.env` file:
```
FORCE_TUNNEL=false
BASE_URL=http://localhost/lequocanh