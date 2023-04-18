jQuery(document).ready(function ($) {
  if ($('.pos-content-area').length > 0) {
    let suqare_connect_btn = $('.pos-content-area .suqare_connect_btn');

    let ajax_url = $('.pos-content-area #ajax_url').val();

    let dumImg = $('.pos-content-area #ajax_url').data('imgdum');

    $.ajax({
      url: ajax_url,
      method: 'POST',
      data: {
        action: 'pos_tokens_connect',
      },
      success: function (response) {
        $('.pos-content-area #square_access_token').val(response);
        if(response !== ''){
          $('.pos-content-area .square__form-container span').text('Disconnect with Square');
          $('.pos-content-area .square__form-container a').attr('href','#');
          $('.pos-content-area .square__form-container a').addClass('square_disconnect_btn');
        }
        // console.log(response);
        $('.loading__square_section').hide();
      },
    });

    // $('.loading__square_section').hide();

    $(document).ready(function (e) {
      // e.preventDefault();

      let square_access_token = $(
        '.pos-content-area #square_access_token'
      ).val();

      // if (square_access_token == '') {
      //   return false;
      // }



      $('.loading__square_section').show();

      $.ajax({
        url: ajax_url,
        method: 'POST',
        data: {
          action: 'pos_square_connect',
          square_access_token: square_access_token,
        },

        success: function (data) {
          const parsedData = JSON.parse(data);

          $('.loading__square_section').hide();

          if (parsedData.length > 0) {
            // console.log(parsedData);

            let html = '';

            parsedData.forEach(function (item) {
              imgURL = item.image_url.length > 0 ? item.image_url : dumImg;

              html += `
              <div class="square-item">
                <div class="square-item-img">
                    <img src=${imgURL} alt="">
                </div>
                <div class="square-item-content">
                    <h3>${item.item_name}</h3>
                    <p>${item.item_description}</p>
                </div>
              </div>
            `;
            });

            $('.square-item-container').html(html);
            $('.square_import_btn').css('display', 'block');

            // console.log(html);

            /* 
            
            =======================================
              IMPORTING PRODUCTS TO WOOCOMMERCE
            =======================================
          */


            $('.square_import_btn').on('click', function (e) {
              e.preventDefault();
        
        
              let square_access_token = $(
                '.pos-content-area #square_access_token'
              ).val();
        
              if(square_access_token == ''){
                return false;
              }
        
              $('.square-modal-confirm').attr('id', 'modalInfo');
            })


            $('.square_connect_confirm').click(function (e) {
              e.preventDefault();

              $(this).attr('data-dismiss',"modal");

              $('.loading__square_section').show();

              $.ajax({
                url: ajax_url,
                method: 'POST',
                data: {
                  action: 'pos_square_pos_import',
                },
                success: function (response) {
                  console.log(response);
                  $('.loading__square_section').hide();
                },
              });
            });

            /* 
            ============================================
              END IMPORTING PRODUCTS TO WOOCOMMERCE
            ============================================
          */
          }
        },
      });

      //   alert('Connecting to Square...');
    });

    $(document).on('click', '.pos-content-area .square__form-container .square_disconnect_btn', function (e) {
      e.preventDefault();

      if($('.pos-content-area .square__form-container span').text() == 'Disconnect with Square'){
        $.ajax({
          url: ajax_url,
          method: 'POST',
          data: {
            action: 'revoke_square_access',
          },
          success: function (response) {
            // console.log(response);
            window.location.href = response;
          },
        });
      }

    });
  }
});
