jQuery(document).ready(function($) {
    var modal = document.getElementById("myModal");
    var span = document.getElementsByClassName("close")[0];
  
    // Abrir el modal automáticamente
    modal.style.display = "block";
  
    // Cerrar el modal al hacer clic en la 'x'
    span.onclick = function() {
      modal.style.display = "none";
    }
  
    // Cerrar el modal al hacer clic fuera del contenido del modal
    window.onclick = function(event) {
      if (event.target == modal) {
        modal.style.display = "none";
      }
    }
  });