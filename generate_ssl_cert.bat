@echo off
echo Generating self-signed SSL certificate for development...

docker run --rm -v %cd%:/work -w /work alpine/openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout ssl-key.pem -out ssl-cert.pem -subj "/C=VN/ST=HCM/L=HoChiMinh/O=Dev/CN=localhost"

echo.
echo SSL certificate generated successfully!
echo Files created: ssl-cert.pem and ssl-key.pem
echo.
pause
