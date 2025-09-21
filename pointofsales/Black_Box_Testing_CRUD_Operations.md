# BLACK BOX TESTING - CRUD OPERATIONS
## Point of Sales (POS) System

**Project**: POS Penjualan System  
**Version**: 1.0  
**Date**: 21 September 2025  
**Tester**: [Nama Tester]  

---

## 1. TEST CASE - ITEMS MANAGEMENT

| Test ID | Test Case | Input | Expected Output | Status | Notes |
|---------|-----------|-------|-----------------|---------|-------|
| IT-001 | Tambah Item | Klik tombol "Tambah Item" | Modal tambah item terbuka | ⏳ | |
| IT-002 | Form Validation | Submit form kosong | Error message "Field ini wajib diisi" | ⏳ | |
| IT-003 | Tambah Item Valid | Isi semua field required | Item berhasil ditambah, halaman refresh | ⏳ | |
| IT-004 | Edit Item | Klik tombol "Edit" di tabel | Modal edit terbuka dengan data terisi | ⏳ | |
| IT-005 | Update Item | Ubah data dan submit | Item berhasil diupdate, halaman refresh | ⏳ | |
| IT-006 | Hapus Item | Klik tombol "Hapus" di tabel | Modal konfirmasi muncul | ⏳ | |
| IT-007 | Konfirmasi Hapus | Klik "Ya" di konfirmasi | Item berhasil dihapus, halaman refresh | ⏳ | |
| IT-008 | Batal Hapus | Klik "Tidak" di konfirmasi | Modal tertutup, item tidak terhapus | ⏳ | |
| IT-009 | Validasi Harga | Input harga negatif | Error message "Harga harus berupa angka positif" | ⏳ | |
| IT-010 | Validasi Stok | Input stok negatif | Error message "Stok harus berupa angka positif" | ⏳ | |

---

## 2. TEST CASE - SALES MANAGEMENT

| Test ID | Test Case | Input | Expected Output | Status | Notes |
|---------|-----------|-------|-----------------|---------|-------|
| SL-001 | Tambah Sales | Klik tombol "Tambah Sales" | Modal tambah sales terbuka | ⏳ | |
| SL-002 | Form Validation | Submit form kosong | Error message "Field ini wajib diisi" | ⏳ | |
| SL-003 | Tambah Sales Valid | Isi semua field required | Sales berhasil ditambah, halaman refresh | ⏳ | |
| SL-004 | Edit Sales | Klik tombol "Edit" di tabel | Modal edit terbuka dengan data terisi | ⏳ | |
| SL-005 | Update Sales | Ubah data dan submit | Sales berhasil diupdate, halaman refresh | ⏳ | |
| SL-006 | Hapus Sales | Klik tombol "Hapus" di tabel | Modal konfirmasi muncul | ⏳ | |
| SL-007 | Konfirmasi Hapus | Klik "Ya" di konfirmasi | Sales berhasil dihapus, halaman refresh | ⏳ | |
| SL-008 | Validasi Tanggal | Input tanggal invalid | Error message validasi tanggal | ⏳ | |
| SL-009 | Validasi Customer | Pilih customer yang tidak ada | Error message customer tidak ditemukan | ⏳ | |
| SL-010 | Status Sales | Ubah status sales | Status berhasil diupdate | ⏳ | |

---

## 3. TEST CASE - CUSTOMER MANAGEMENT

| Test ID | Test Case | Input | Expected Output | Status | Notes |
|---------|-----------|-------|-----------------|---------|-------|
| CS-001 | Tambah Customer | Klik tombol "Tambah Customer" | Modal tambah customer terbuka | ⏳ | |
| CS-002 | Form Validation | Submit form kosong | Error message "Field ini wajib diisi" | ⏳ | |
| CS-003 | Tambah Customer Valid | Isi semua field required | Customer berhasil ditambah, halaman refresh | ⏳ | |
| CS-004 | Edit Customer | Klik tombol "Edit" di tabel | Modal edit terbuka dengan data terisi | ⏳ | |
| CS-005 | Update Customer | Ubah data dan submit | Customer berhasil diupdate, halaman refresh | ⏳ | |
| CS-006 | Hapus Customer | Klik tombol "Hapus" di tabel | Modal konfirmasi muncul | ⏳ | |
| CS-007 | Konfirmasi Hapus | Klik "Ya" di konfirmasi | Customer berhasil dihapus, halaman refresh | ⏳ | |
| CS-008 | Validasi Email | Input email invalid | Error message format email salah | ⏳ | |
| CS-009 | Validasi Telepon | Input nomor telepon invalid | Error message format telepon salah | ⏳ | |
| CS-010 | Duplicate Customer | Input nama customer yang sudah ada | Error message customer sudah ada | ⏳ | |

---

## 4. TEST CASE - TRANSACTION MANAGEMENT

| Test ID | Test Case | Input | Expected Output | Status | Notes |
|---------|-----------|-------|-----------------|---------|-------|
| TR-001 | Tambah Transaction | Klik tombol "Tambah Transaction" | Modal tambah transaction terbuka | ⏳ | |
| TR-002 | Form Validation | Submit form kosong | Error message "Field ini wajib diisi" | ⏳ | |
| TR-003 | Tambah Transaction Valid | Isi semua field required | Transaction berhasil ditambah, halaman refresh | ⏳ | |
| TR-004 | Edit Transaction | Klik tombol "Edit" di tabel | Modal edit terbuka dengan data terisi | ⏳ | |
| TR-005 | Update Transaction | Ubah data dan submit | Transaction berhasil diupdate, halaman refresh | ⏳ | |
| TR-006 | Hapus Transaction | Klik tombol "Hapus" di tabel | Modal konfirmasi muncul | ⏳ | |
| TR-007 | Konfirmasi Hapus | Klik "Ya" di konfirmasi | Transaction berhasil dihapus, halaman refresh | ⏳ | |
| TR-008 | Validasi Jumlah | Input jumlah negatif | Error message jumlah harus positif | ⏳ | |
| TR-009 | Validasi Item | Pilih item yang tidak ada | Error message item tidak ditemukan | ⏳ | |
| TR-010 | Kalkulasi Total | Input harga dan jumlah | Total otomatis terhitung | ⏳ | |

---

## 5. TEST CASE - USER MANAGEMENT

| Test ID | Test Case | Input | Expected Output | Status | Notes |
|---------|-----------|-------|-----------------|---------|-------|
| US-001 | Tambah User | Klik tombol "Tambah Pengguna" | Modal tambah user terbuka | ⏳ | |
| US-002 | Form Validation | Submit form kosong | Error message "Field ini wajib diisi" | ⏳ | |
| US-003 | Tambah User Valid | Isi semua field required | User berhasil ditambah, halaman refresh | ⏳ | |
| US-004 | Edit User | Klik tombol "Edit" di tabel | Modal edit terbuka dengan data terisi | ⏳ | |
| US-005 | Update User | Ubah data dan submit | User berhasil diupdate, halaman refresh | ⏳ | |
| US-006 | Hapus User | Klik tombol "Hapus" di tabel | Modal konfirmasi muncul | ⏳ | |
| US-007 | Konfirmasi Hapus | Klik "Ya" di konfirmasi | User berhasil dihapus, halaman refresh | ⏳ | |
| US-008 | Validasi Username | Input username yang sudah ada | Error message username sudah digunakan | ⏳ | |
| US-009 | Validasi Password | Input password kurang dari 6 karakter | Error message password minimal 6 karakter | ⏳ | |
| US-010 | Validasi Role | Pilih role yang tidak valid | Error message role tidak valid | ⏳ | |

---

## 6. TEST CASE - ERROR HANDLING

| Test ID | Test Case | Input | Expected Output | Status | Notes |
|---------|-----------|-------|-----------------|---------|-------|
| ER-001 | Network Error | Matikan internet saat submit | Error message "Terjadi kesalahan sistem" | ⏳ | |
| ER-002 | Server Error | Submit data invalid | Error message dari server | ⏳ | |
| ER-003 | Validation Error | Input data tidak valid | Error message spesifik per field | ⏳ | |
| ER-004 | Duplicate Data | Submit data yang sudah ada | Error message "Data sudah ada" | ⏳ | |
| ER-005 | Database Error | Simulasi database down | Error message "Database tidak tersedia" | ⏳ | |
| ER-006 | Session Timeout | Login expired | Redirect ke halaman login | ⏳ | |
| ER-007 | Permission Denied | Akses halaman tanpa izin | Error message "Akses ditolak" | ⏳ | |
| ER-008 | File Upload Error | Upload file terlalu besar | Error message "File terlalu besar" | ⏳ | |

---

## 7. TEST CASE - UI/UX TESTING

| Test ID | Test Case | Input | Expected Output | Status | Notes |
|---------|-----------|-------|-----------------|---------|-------|
| UI-001 | Modal Responsive | Buka modal di berbagai ukuran layar | Modal tampil dengan baik | ⏳ | |
| UI-002 | Loading State | Klik submit form | Tombol menampilkan loading spinner | ⏳ | |
| UI-003 | Notification | Setelah operasi berhasil/gagal | Notification muncul di pojok kanan | ⏳ | |
| UI-004 | Auto Refresh | Setelah CRUD berhasil | Halaman otomatis refresh | ⏳ | |
| UI-005 | Form Reset | Buka modal tambah | Form dalam keadaan kosong | ⏳ | |
| UI-006 | Keyboard Navigation | Navigasi dengan Tab | Focus berpindah dengan benar | ⏳ | |
| UI-007 | Modal Close | Klik X atau klik di luar modal | Modal tertutup | ⏳ | |
| UI-008 | Table Pagination | Klik next/previous page | Data berubah sesuai halaman | ⏳ | |
| UI-009 | Search Function | Input kata kunci di search | Data terfilter sesuai pencarian | ⏳ | |
| UI-010 | Export Function | Klik tombol export | File download dimulai | ⏳ | |

---

## 8. TEST CASE - BROWSER COMPATIBILITY

| Test ID | Test Case | Input | Expected Output | Status | Notes |
|---------|-----------|-------|-----------------|---------|-------|
| BC-001 | Chrome | Test semua CRUD di Chrome | Semua fungsi berjalan normal | ⏳ | |
| BC-002 | Firefox | Test semua CRUD di Firefox | Semua fungsi berjalan normal | ⏳ | |
| BC-003 | Edge | Test semua CRUD di Edge | Semua fungsi berjalan normal | ⏳ | |
| BC-004 | Safari | Test semua CRUD di Safari | Semua fungsi berjalan normal | ⏳ | |
| BC-005 | Mobile Chrome | Test di mobile Chrome | Responsive dan berfungsi | ⏳ | |
| BC-006 | Mobile Safari | Test di mobile Safari | Responsive dan berfungsi | ⏳ | |

---

## 9. TEST CASE - PERFORMANCE TESTING

| Test ID | Test Case | Input | Expected Output | Status | Notes |
|---------|-----------|-------|-----------------|---------|-------|
| PF-001 | Load Time | Buka halaman CRUD | Halaman load < 3 detik | ⏳ | |
| PF-002 | Response Time | Submit form | Response < 2 detik | ⏳ | |
| PF-003 | Memory Usage | Buka beberapa modal | Tidak ada memory leak | ⏳ | |
| PF-004 | Large Dataset | Load 1000+ records | Halaman tetap responsif | ⏳ | |
| PF-005 | Concurrent Users | 10 user akses bersamaan | Sistem tetap stabil | ⏳ | |

---

## 10. TEST CASE - SECURITY TESTING

| Test ID | Test Case | Input | Expected Output | Status | Notes |
|---------|-----------|-------|-----------------|---------|-------|
| SC-001 | XSS Prevention | Input script malicious | Script tidak dieksekusi | ⏳ | |
| SC-002 | SQL Injection | Input SQL injection | Data tidak terpengaruh | ⏳ | |
| SC-003 | Access Control | Akses halaman tanpa login | Redirect ke login | ⏳ | |
| SC-004 | Role Permission | Akses halaman dengan role salah | Error atau redirect | ⏳ | |
| SC-005 | CSRF Protection | Submit form dari external site | Request ditolak | ⏳ | |
| SC-006 | Input Sanitization | Input karakter khusus | Data disanitasi dengan benar | ⏳ | |

---

## 11. TEST CASE - INTEGRATION TESTING

| Test ID | Test Case | Input | Expected Output | Status | Notes |
|---------|-----------|-------|-----------------|---------|-------|
| IN-001 | Items-Sales Integration | Tambah sales dengan item | Item terhubung dengan benar | ⏳ | |
| IN-002 | Sales-Transaction Integration | Tambah transaction dengan sales | Sales terhubung dengan benar | ⏳ | |
| IN-003 | Customer-Sales Integration | Tambah sales dengan customer | Customer terhubung dengan benar | ⏳ | |
| IN-004 | Data Consistency | Update data di satu tabel | Data konsisten di semua tabel | ⏳ | |
| IN-005 | Foreign Key Constraint | Hapus data yang digunakan | Error constraint violation | ⏳ | |

---

## TESTING STATUS LEGEND

| Symbol | Status | Description |
|--------|--------|-------------|
| ⏳ | Pending | Belum ditest |
| ✅ | Pass | Test berhasil |
| ❌ | Fail | Test gagal |
| 🔄 | In Progress | Sedang ditest |
| ⚠️ | Warning | Ada issue minor |
| 🚫 | Blocked | Tidak bisa ditest |

---

## PRIORITY TESTING

### HIGH PRIORITY (Critical)
- IT-001, SL-001, CS-001, TR-001, US-001 (Tombol Tambah)
- IT-003, SL-003, CS-003, TR-003, US-003 (Tambah Data Valid)
- ER-001, ER-002, ER-003 (Error Handling)

### MEDIUM PRIORITY (Important)
- Edit dan Hapus operations
- Form validation
- UI/UX testing

### LOW PRIORITY (Nice to Have)
- Performance testing
- Browser compatibility
- Security testing

---

## TEST ENVIRONMENT

**Operating System**: Windows 10/11  
**Web Server**: XAMPP (Apache + MySQL + PHP)  
**Database**: MySQL 8.0  
**PHP Version**: 8.0+  
**Browser**: Chrome, Firefox, Edge, Safari  

---

## TEST DATA

**Test User Accounts**:
- Admin: admin@pos.com / admin123
- Manager: manager@pos.com / manager123
- Petugas: petugas@pos.com / petugas123

**Test Data Sets**:
- Items: 50+ sample items
- Customers: 30+ sample customers
- Sales: 100+ sample sales records
- Transactions: 200+ sample transactions
- Users: 5+ sample users

---

## NOTES

1. **Testing Approach**: Black Box Testing - Test functionality tanpa melihat kode internal
2. **Test Coverage**: 100% CRUD operations untuk semua modul
3. **Regression Testing**: Test semua fungsi setelah setiap update
4. **Documentation**: Update status test secara real-time
5. **Bug Tracking**: Catat semua bug yang ditemukan dengan detail

---

**Document Version**: 1.0  
**Last Updated**: 21 September 2025  
**Next Review**: 28 September 2025
