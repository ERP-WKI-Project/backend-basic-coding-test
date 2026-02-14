## Dokumentasi

### auth untuk backoffice
- Tambahan auth login untuk backoffice agar bisa mendapat bearer token untuk akses endpoint-endpoint backoffice.
- contoh request curl :
``` 
postman request POST 'http://backend-basic-coding-test.test:8080/api/backoffice/v1/auth/login' \
  --header 'Content-Type: application/json' \
  --body '{
    "pin" : "000001",
    "password" : "password"
}'
```

```
