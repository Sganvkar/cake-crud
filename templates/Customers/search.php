<?php
// This view renders the frontend UI: checkboxes, filters, and the Send button.
?>
<div style="max-width:900px; margin:20px auto; font-family:Arial;">
    <h2>Customer Search (XML API)</h2>

    <!-- FORM SECTION -->
    <form id="cusForm" onsubmit="return false;">

        <!-- Field selection -->
        <fieldset style="display: flex;justify-content: space-between;">
            <legend>Select Fields to Return</legend>

            <!-- Each checkbox corresponds to a column to request -->
            <label><input type="checkbox" name="fields" value="BillTo" checked> BillTo</label><br>
            <label><input type="checkbox" name="fields" value="CreatedDate" checked> CreatedDate</label><br>
            <label><input type="checkbox" name="fields" value="CustomerCode" checked> CustomerCode</label><br>
            <label><input type="checkbox" name="fields" value="CustomerName" checked> CustomerName</label><br>
            <label><input type="checkbox" name="fields" value="WarehouseCode" checked> WarehouseCode</label><br>
        </fieldset>

        <!-- Filter section -->
        <fieldset style="margin-top:15px;">
            <legend>Filters (Like / Not Like)</legend>

            <div style="display:flex; gap:20px; flex-wrap:wrap;">

                <!-- BillTo -->
                <div style="min-width:180px;">
                    <strong>BillTo</strong><br>
                    <div style="display:flex; gap:6px; margin-top:4px;">
                        <input type="text" id="BillToLike" placeholder="Like: A%" style="width:85px;">
                        <input type="text" id="BillToNotLike" placeholder="Not: Z%" style="width:85px;">
                    </div>
                </div>

                <!-- CustomerCode -->
                <div style="min-width:180px;">
                    <strong>CustomerCode</strong><br>
                    <div style="display:flex; gap:6px; margin-top:4px;">
                        <input type="text" id="CustomerCodeLike" placeholder="Like" style="width:85px;">
                        <input type="text" id="CustomerCodeNotLike" placeholder="Not" style="width:85px;">
                    </div>
                </div>

                <!-- CustomerName -->
                <div style="min-width:180px;">
                    <strong>CustomerName</strong><br>
                    <div style="display:flex; gap:6px; margin-top:4px;">
                        <input type="text" id="CustomerNameLike" placeholder="Like" style="width:85px;">
                        <input type="text" id="CustomerNameNotLike" placeholder="Not" style="width:85px;">
                    </div>
                </div>

                <!-- WarehouseCode -->
                <div style="min-width:180px;">
                    <strong>WarehouseCode</strong><br>
                    <div style="display:flex; gap:6px; margin-top:4px;">
                        <input type="text" id="WarehouseCodeLike" placeholder="Like" style="width:85px;">
                        <input type="text" id="WarehouseCodeNotLike" placeholder="Not" style="width:85px;">
                    </div>
                </div>

            </div>
        </fieldset>


        <!-- Record Limit -->
        <div style="margin-top:10px;">
            Record Limit:
            <input type="number" id="RecordLimit" value="99" min="1">
        </div>

        <!-- SEND BUTTON -->
        <button id="sendBtn" style="margin-top:15px;">Send</button>

    </form>

    <!-- Response goes here -->
    <div id="responseArea" style="margin-top:30px;"></div>
</div>


<!-- jQuery for Ajax/XML handling -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
/* --------------------------------------------------------------
   Build XML request based on user input
-------------------------------------------------------------- */
function buildRequestXml(fields, filters, limit) {

    // Start XML root element
    let xml = '<' + '?xml version="1.0" encoding="UTF-8"?' + '>';

    xml += '<CusGetCustomersRequest xmlns="http://www.pronto.net/cus/1.0.0">';

    // Add RecordLimit
    xml += `<RecordLimit>${limit}</RecordLimit>`;

    // Empty <Parameters> (Pronto style)
    xml += '<Parameters></Parameters>';

    // Build Filters XML
    xml += '<Filters>';
    for (let field in filters) {
        let like = filters[field].like;
        let notLike = filters[field].notlike;

        // Only include tag if user typed something
        if (like || notLike) {
            xml += `<${field}>`;
            xml += `<Like>${like}</Like>`;
            xml += `<NotLike>${notLike}</NotLike>`;
            xml += `</${field}>`;
        }
    }
    xml += '</Filters>';

    // Build <RequestFields>
    xml += '<RequestFields><Customers><Customer>';
    fields.forEach(f => {
        xml += `<${f} />`;
    });
    xml += '</Customer></Customers></RequestFields>';

    xml += '</CusGetCustomersRequest>';

    return xml;
}

/* --------------------------------------------------------------
   Convert XML response → HTML table
-------------------------------------------------------------- */
function buildTableFromXml(xmlText) {
    let xml = $.parseXML(xmlText);
    let $xml = $(xml);
    let $customers = $xml.find('Customer');

    if ($customers.length === 0) {
        return "<div>No results found.</div>";
    }

    // Identify columns using the first customer
    let cols = [];
    $customers.first().children().each(function() {
        cols.push(this.nodeName);
    });

    // Build table
    let html = "<table border='1' cellpadding='6'><thead><tr>";
    cols.forEach(c => html += `<th>${c}</th>`);
    html += "</tr></thead><tbody>";

    // Add rows
    $customers.each(function() {
        html += "<tr>";
        let $cust = $(this);

        cols.forEach(c => {
            let val = $cust.find(c).text();
            html += `<td>${val}</td>`;
        });

        html += "</tr>";
    });

    html += "</tbody></table>";

    return html;
}

/* --------------------------------------------------------------
   Send XML request → API
-------------------------------------------------------------- */
$('#sendBtn').on('click', function() {

    // Collect selected fields
    let fields = [];
    $('input[name="fields"]:checked').each(function() {
        fields.push($(this).val());
    });

    // Collect filters
    let filters = {
        BillTo: {
            like: $('#BillToLike').val(),
            notlike: $('#BillToNotLike').val()
        },
        CustomerCode: {
            like: $('#CustomerCodeLike').val(),
            notlike: $('#CustomerCodeNotLike').val()
        },
        CustomerName: {
            like: $('#CustomerNameLike').val(),
            notlike: $('#CustomerNameNotLike').val()
        },
        WarehouseCode: {
            like: $('#WarehouseCodeLike').val(),
            notlike: $('#WarehouseCodeNotLike').val()
        }
    };

    // Record limit
    let limit = $('#RecordLimit').val();

    // Build request XML
    let xmlReq = buildRequestXml(fields, filters, limit);

    // Show loading text
    $('#responseArea').html("<div>Loading...</div>");

    // Send AJAX request
    $.ajax({
        url: '/api/customers/get',
        method: 'POST',
        data: xmlReq,
        contentType: 'application/xml; charset=UTF-8',
        dataType: 'text', // response is XML text
        success: function(respText) {
            let htmlTable = buildTableFromXml(respText);
            $('#responseArea').html(htmlTable);
        },
        error: function(xhr) {
            $('#responseArea').html('<div style="color:red;">Error</div><pre>' + xhr.responseText + '</pre>');
        }
    });
});
</script>
