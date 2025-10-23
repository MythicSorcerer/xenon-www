async function loadHeaders() {
      const res = await fetch('../headers.php');
      const html = await res.text();
      document.getElementById('headers').innerHTML = html;
}

loadHeaders();
