<h2>Pronto Customer Lookup Demo</h2>

<button id="btnCustomer" class="btn btn-primary">Get Customer Details</button>

<div id="result" style="margin-top:20px; white-space:pre-wrap; font-family: monospace;"></div>

<script>
document.getElementById('btnCustomer').onclick = async () => {
    const res = await fetch('/api/customer');
    let xmlText = await res.text();

    // Simple formatting: escape XML
    document.getElementById('result').innerText = xmlText;
};
</script>
