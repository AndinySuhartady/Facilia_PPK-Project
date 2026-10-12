</main><?php if(isLoggedIn()): ?></div></div><?php endif; ?>
<style>
/* Gaya inti dialog konfirmasi ditanam langsung di halaman, supaya dialog tetap tampil
   di tengah layar & di atas sidebar walaupun browser masih memakai style.css lama (cache).
   Tanpa ini, dialog sempat muncul di kiri bawah tertutup sidebar dan tombolnya tidak bisa diklik. */
.confirm-modal[hidden]{display:none!important}
.confirm-modal{position:fixed;inset:0;z-index:2000;display:grid;place-items:center;padding:20px}
.confirm-modal__backdrop{position:absolute;inset:0;background:rgba(15,23,42,.58)}
.confirm-modal__panel{position:relative;z-index:1;width:min(440px,100%);padding:28px;background:#fff;border-radius:18px;box-shadow:0 24px 70px rgba(15,23,42,.28);text-align:center}
.confirm-modal__actions{display:flex;justify-content:center;gap:10px;margin-top:24px}
</style>
<script>
// Modal bersama untuk pengajuan dan pembatalan reservasi pengguna.
window.confirmReservationAction = function(form, message) {
    if (!form.reportValidity()) return false;
    if (document.getElementById('userReservationModal')) return false;
    const modal = document.createElement('div');
    modal.id = 'userReservationModal'; modal.className = 'confirm-modal';
    modal.innerHTML = '<div class="confirm-modal__backdrop"></div><section class="confirm-modal__panel" role="dialog" aria-modal="true" aria-labelledby="userReservationTitle"><div class="confirm-modal__icon">!</div><h2 id="userReservationTitle"></h2><p id="userReservationText"></p><div class="confirm-modal__actions"><button type="button" class="confirm-modal__cancel">Kembali</button><button type="button" class="confirm-modal__next">Lanjutkan</button></div></section>';
    document.body.appendChild(modal); document.body.classList.add('modal-open');
    const title = modal.querySelector('h2'), text = modal.querySelector('p');
    const cancel = modal.querySelector('button'), next = modal.querySelectorAll('button')[1];
    const previousFocus = document.activeElement;
    let step = 1, submitted = false;
    const isCancel = form.action.includes('/reservation/cancel');
    function render() {
        title.textContent = step === 1 ? message : 'Konfirmasi terakhir, keputusan ini sudah benar?';
        text.textContent = step === 1 ? 'Periksa kembali fasilitas, jadwal, dan tujuan penggunaan sebelum melanjutkan.' : 'Keputusan akan disimpan setelah Anda menekan tombol konfirmasi.';
        next.textContent = step === 1 ? 'Lanjutkan' : (isCancel ? 'Ya, Batalkan' : 'Ya, Ajukan');
        cancel.focus();
    }
    function close() {
        modal.remove(); document.body.classList.remove('modal-open');
        document.removeEventListener('keydown', keyboard);
        if (previousFocus) previousFocus.focus();
    }
    function keyboard(e) {
        if (e.key === 'Escape') close();
        if (e.key === 'Tab') {
            e.preventDefault(); (document.activeElement === cancel ? next : cancel).focus();
        }
    }
    cancel.onclick = function() { if (step === 2) { step = 1; render(); } else close(); };
    modal.querySelector('.confirm-modal__backdrop').onclick = close;
    next.onclick = function() {
        if (step === 1) { step = 2; render(); return; }
        if (submitted) return;
        if (!form.reportValidity()) { close(); return; }
        submitted = true; next.disabled = true;
        form.querySelector('[name="confirmed"]').value = '1';
        HTMLFormElement.prototype.submit.call(form);
    };
    document.addEventListener('keydown', keyboard); render();
    return false;
};
</script>
</body></html>
