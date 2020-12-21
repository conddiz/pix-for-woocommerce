// jQuery(document).ready( function($) {
//     if ($("div.wcpix-response-output")){
//         console.log('div mensagem existe');
//     }
// });

jQuery( "img.wcpix-img-copy-code" ).click(function() {
    copyCode();
});

jQuery( "button.wcpix-button-copy-code" ).click(function() {
    copyCode();
});

function copyCode() {
    let result;
    try {
        var copyText = document.getElementById("copiar");
        copyText.type = "text";
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        document.execCommand("copy"); 
        copyText.type = "hidden";
        result = true;
    }
    catch (e) {
        console.log(e);
        result = false;
    }

    if (result){
        if (jQuery("div.wcpix-response-output")){
            jQuery("div.wcpix-response-output").show();
        }else{
            alert('Code copiado!');
        }
    }else{
        alert('Erro ao copiar');
    }
}