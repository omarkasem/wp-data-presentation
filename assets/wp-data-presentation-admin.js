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

        },
        
        showError:function(message){
            $('div[data-name="validation"] input').trigger('click').val(0);
            
            $('.wpdp_success').css('opacity','0');
            alert(message);
            return;
        },

        getFileData:function(file){
            $('.wpdp_loader').show();
            
            $.ajax({
                url: wpdp_obj.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpdp_get_data',
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