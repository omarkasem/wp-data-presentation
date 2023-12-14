jQuery(document).ready(function($){

    var allowed_types = ['xlsx','csv'];
    var wpDataPresentation = {
        init: function () {

            $('.wpdp_validate_file').on('click',function(e){
                e.preventDefault();
                let file = wpDataPresentation.validateFile();
                if(file){
                    wpDataPresentation.getFileData(file);
                }
            });

            $(document).on("click",".wpdp_copy",wpDataPresentation.copyShortcode);

        },
        
        showError:function(message){
            $('div[data-name="validation"] input').trigger('click').val(0);
            
            $('.wpdp_success').css('opacity','0');
            alert(message);
            return;
        },

        copyShortcode: function (e) {
            e.preventDefault();
            var input = $(this).parent().find('input');
            wpDataPresentation.copyToClipboard(input);
            $('<span>COPIED!</span>').insertAfter(input).delay(500).fadeOut( function(){ $(this).remove();});
        },

        copyToClipboard: function (element) {
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val($(element).val()).select();
            document.execCommand("copy");
            $temp.remove();
        },

        getFileData:function(file){
            $('.wpdp_loader').show();
            let post_id = $('#post_ID').val();
            $.ajax({
                url: wpdp_obj.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpdp_get_data',
                    post_id:post_id,
                    file:file,
                },
                success: function(response) {
                    if(response.success == false){
                        wpDataPresentation.showError(response.data[0]);
                        return;
                    }
                    $('.wpdp_loader').hide();

                    $('.wpdp_success').css('opacity','1');
                    $('div[data-name="validation"] input').trigger('click').val(1);
                }
            });
        
        },

        validateFile:function(){
            let file;
            if($('#acf-field_657aa840cb9c5').val() == 'Upload'){
                file = $('.acf-file-uploader .file-info a[data-name="filename"]').attr('href');
            }else{
                file = $('div[data-name="excel_file_url"] input').val();
            }


            if(file == ''){
                wpDataPresentation.showError('Please upload a file or insert URL first.');
                return;
            }

            if(!allowed_types.includes(wpDataPresentation.getExtension(file))){
                wpDataPresentation.showError('File type has to be xlsx or csv');
                return;
            }

            return file;

        },
        getExtension:function(url) {
            return url.split('.').pop().split(/\#|\?/)[0];
        }
        

    }




    wpDataPresentation.init();
});