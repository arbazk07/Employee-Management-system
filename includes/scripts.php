<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
/* Init tooltips */
document.addEventListener('DOMContentLoaded', () => {
  [...document.querySelectorAll('[data-bs-toggle="tooltip"]')].map(el => new bootstrap.Tooltip(el));
  /* Live date */
  const el = document.getElementById('date-display');
  if (el) el.textContent = new Date().toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
});
/* Shared enterprise tooltip style */
const entTip = {
  backgroundColor:'#0F172A',titleColor:'#F8FAFC',bodyColor:'#94A3B8',
  borderColor:'#334155',borderWidth:1,
  titleFont:{family:'Inter',size:13,weight:'600'},
  bodyFont:{family:'Inter',size:12},
  padding:10,cornerRadius:6,boxPadding:6,usePointStyle:true
};
/* SweetAlert2 shared delete helper */
function confirmDelete(url, name, entity, onSuccess) {
  Swal.fire({
    title: `Remove ${entity}`,
    html: `Are you sure you want to remove <strong>${name}</strong>? This action cannot be undone.`,
    icon:'warning', iconColor:'#EF4444',
    showCancelButton:true,
    confirmButtonText:'Delete Record', cancelButtonText:'Cancel',
    reverseButtons:true, focusCancel:true
  }).then(r => {
    if (!r.isConfirmed) return;
    fetch(url, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'confirm=1'})
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          Swal.fire({title:'Deleted',html:`<strong>${name}</strong> has been removed.`,icon:'success',iconColor:'#10B981',confirmButtonText:'Close',timer:2500,timerProgressBar:true})
            .then(() => { if(onSuccess) onSuccess(); else location.reload(); });
        } else {
          Swal.fire({title:'Error',text:data.message||'Could not delete record.',icon:'error',iconColor:'#EF4444'});
        }
      });
  });
}
</script>
