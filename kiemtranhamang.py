import phonenumbers
import requests
import json
from phonenumbers import geocoder, carrier

# Nhập số điện thoại cần tra
number = input("Nhập số điện thoại (có mã quốc gia, ví dụ +84901234567): ")

#Kiểm Tra My Viettel
def sms(number):
    url = f"https://viettel.bachtuanduy.vn/vt.php?phone={number}"
    try:
        response = requests.get(url, timeout=5)
        response.raise_for_status()
        t = response.json()
    except Exception as e:
        print("Lỗi khi gọi API:", e)
        return

    if t.get("status", {}).get("code") == "00" and "data" in t:
        data = t["data"]
        ten = data.get("displayNameAccent", "Không có tên")
        sodt = data.get("twofaChannelValue", "Không có số")
        print(f"Họ tên: {ten}")
        print(f"SĐT : {sodt}")
    else:
        print("Không tìm thấy thông tin hoặc số điện thoại không hợp lệ.")




# Lấy thông tin quốc gia / khu vực
ch_number = phonenumbers.parse(number, "VN")
print("Quốc gia / Khu vực:", geocoder.description_for_number(ch_number, "vi"))

# Lấy thông tin nhà mạng
service_number = phonenumbers.parse(number, "VN")
print("Nhà mạng:", carrier.name_for_number(service_number, "vi"))

sms(number)