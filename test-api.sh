#!/bin/bash

# Script untuk test API endpoint absensi.bprsbtb.co.id
echo "Testing Absensi API Endpoint..."
echo "================================"

# Test endpoint tanpa token (harus 401)
echo "1. Testing without token:"
curl -s -o /dev/null -w "Status: %{http_code}\n" https://absensi.bprsbtb.co.id/api/daily-activities

echo ""

# Test endpoint dengan token dari .env
echo "2. Testing with token from .env:"
TOKEN=$(grep ABSENSI_API_TOKEN .env | cut -d'=' -f2)
if [ -n "$TOKEN" ]; then
    RESPONSE=$(curl -s -H "Authorization: Bearer $TOKEN" https://absensi.bprsbtb.co.id/api/daily-activities)
    STATUS=$(curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $TOKEN" https://absensi.bprsbtb.co.id/api/daily-activities)

    echo "Status: $STATUS"
    if [ "$STATUS" -eq 200 ]; then
        echo "✅ API endpoint mengirim data daily activity!"
        echo "Sample data:"
        echo "$RESPONSE" | head -20
    else
        echo "❌ API endpoint tidak dapat diakses atau token tidak valid"
        echo "Response: $RESPONSE"
    fi
else
    echo "❌ Token tidak ditemukan di .env"
fi

echo ""
echo "================================"
echo "Untuk mendapatkan token yang valid:"
echo "1. Login ke https://absensi.bprsbtb.co.id"
echo "2. Dapatkan JWT token dari response login"
echo "3. Update ABSENSI_API_TOKEN di .env dan .env.production"
