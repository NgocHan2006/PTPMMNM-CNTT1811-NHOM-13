HUONG DAN CHAY DU AN QUAN LY BIEU MAU

1. Giai nen thu muc QuanLyBieuMau_PHP vao:
   C:\xampp2\htdocs\QuanLyBieuMau
   (Neu ban dang co du an, sao luu truoc va chep cac file can thiet vao dung vi tri.)

2. Mo config/database.php va kiem tra:
   $host = '127.0.0.1';
   $dbname = 'quanlybieumau';
   $username = 'root';
   $password = '';
   Neu MySQL co mat khau, sua bien $password.

3. Bat Apache va MySQL trong XAMPP.

4. Mo:
   http://localhost/QuanLyBieuMau/index.php

5. Cac trang:
   index.php       Tong quan
   forms.php       Danh sach bieu mau
   form_create.php Tao bieu mau moi (ban nhap)
   fill.php?id=1   Xem/dien bieu mau mau
   responses.php   Danh sach cau tra loi
   export.php      Xuat CSV
   response_view.php?id=1 Xem mot phan hoi

LUU Y:
- Du an nay la ban nen giao dien va luong thu thap du lieu. Chua co dang nhap that; khong dua len Internet khi chua bo sung xac thuc va phan quyen.
- Tai khoan demo trong database chua co mat khau dang nhap.
- Form moi hien duoc tao ban nhap rong; chuc nang giao dien them/sua truong, tao phien ban va quan ly dieu kien can duoc phat trien tiep.
- Khi submit, server kiem tra version dang cong bo, truong hop le, truong bat buoc, lua chon, so, ngay, email va mot so validation.
- Khong dung eval; dieu kien duoc doc tu bang conditions.
