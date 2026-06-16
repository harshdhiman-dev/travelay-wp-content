<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script src="https://code.jquery.com/jquery-3.3.1.js"></script>
<?php
global $user_pid;
?>
<link href="<?php echo ADIVAHA__PLUGIN_URL; ?>/asset/css/icon" rel="stylesheet">
<link id="pagestyle" href="<?php echo ADIVAHA__PLUGIN_URL; ?>/asset/css/material-dashboard.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
  .tabcontent {
    display: none
  }

  .headingone {
    font-size: 38px;
  }

  .tab {
    display: flex;
    flex-direction: column;
    width: auto;
    float: left;
    background: #1f262a;
    height: 100vh;
    box-shadow: 0 4px 7px -1px rgb(0 0 0 / 11%), 0 2px 4px -1px rgb(0 0 0 / 7%);
    align-items: unset;
    justify-content: flex-start;
    position: relative;
    bottom: 0;
  }

  .justify-content-space-between {
    justify-content: space-between
  }

  .tab_div {
    display: flex;
    justify-content: space-between;
  }

  .tab_div2 {
    border-radius: 5px;
    background: transparent;
    box-shadow: rgb(0 0 0 / 24%) 0px 3px 8px;
    padding: 30px 0px;
    display: flex;
    justify-content: center;
    flex-direction: column;
    align-items: center;
    width: 20%;
    border: 2px dashed #f0f0f1;
  }

  .mode {
    color: #fff;
    font-size: 19px;
    margin: 0;
    font-weight: bold;
    width: 100%;
    text-align: center;
  }

  .mode .Live {
    color: #3F0;
  }

  .mode .Test {
    color: #f4a535;
  }

  .Balance {
    font-size: 15px;
    margin: 0px;
    text-align: center;
    margin-top: 17px;
    width: 100%;
    border-top: 0px;
    padding-top: 17px;
    color: #fff;
  }

  .Balance span {
    display: block;
    font-size: 39px;
    color: #35f49b;
    font-weight: bold;
    margin-top: -12px;
  }
</style>

<body class="g-sidenav-show  bg-gray-200" id="adivaha-body">
  <main class="main-content position-relative   ps ps--active-y">
    <nav class="navbar-main navbar-expand-lg bg-gradient-dark  bg-white shadow-none sidenav-header" id="navbarBlur" data-scroll="true">
      <div class="container-fluid p-5 mt-4 h-100">
        <div class="tab_div">
          <div class="tab_div1"> <img src="https://www.adivaha.com/images/logo.png" style="max-width:200px">
            <nav class="h-100 mt-6">
              <p class="ms-1 mb-0 font-weight-bold text-white headingone">Wordpress adivaha&reg; Plugin</p>
              <p class="ms-1  mb-0 heading2">WordPress Travel Plugin and White label Travel Solutions to the travel agencies.</p>
            </nav>
          </div>
		   <a href="https://www.adivaha.com/wordpress-travel-themes.html" target="_blank"><img src="<?php echo ADIVAHA__PLUGIN_URL; ?>/images/rightsideimg.png" style="    width: 549px"></a>
        </div>
      </div>
    </nav>
    <div class="tab" style="width: 17%">
      
	  <a class="tablinks btn bg-gradient-primary" href="https://www.adivaha.com/documentations/wordpress-travel-plugin.html" target="_blank">
        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center"> <i class="material-icons opacity-10">dashboard</i> </div>
        <span class="nav-link-text ms-1">Installtion Guide</span>
      </a>
	  <a class="tablinks btn bg-gradient-primary" href="https://www.adivaha.com/wordpress-travel-themes.html" target="_blank">
        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center"> <i class="material-icons opacity-10">dashboard</i> </div>
        <span class="nav-link-text ms-1">Premium WP Themes</span>
      </a>
	  <a class="tablinks btn bg-gradient-primary" href="https://www.adivaha.com/contact-us.html" target="_blank">
        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center"> <i class="material-icons opacity-10">dashboard</i> </div>
        <span class="nav-link-text ms-1">Raise Ticket</span>
      </a>
	  <a class="tablinks btn bg-gradient-primary" href="https://my.adivaha.com/" target="_blank">
        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center"> <i class="material-icons opacity-10">dashboard</i> </div>
        <span class="nav-link-text ms-1">BackOffice</span>
      </a>
    </div>
    <div class="container py-4 tabcontent" id="api-setting" style="display:block">
      <form method="POST" id="general-settings">
        <div class="row">
          <div class="col-xl-12 col-sm-6 mb-xl-0 mb-4 mt-7">
            <p class="heading_genral py-1" style="    border-bottom: 1px solid #cccccc12!important">GENERAL SETTINGS
              </p>
			 <div class="col-xl-12 col-sm-6 mb-xl-0 mb-4" style="border: 1px dashed #8BC34A;padding: 20px;border-bottom: 1px dashed #000;margin-bottom: 40px !important;background: #c9d73a0a;display: flex;flex-direction:column;justify-content: space-between;align-items: baseline;">
	<div style="gap: 7px;display: flex;flex-direction: row;align-items: center;border-bottom: 1px solid #cccccc36;width: 100%;padding-bottom: 10px;margin-bottom: 10px;;font-weight: 600;color: #000;">Obtain your Partner ID (PID): WhatsApp us at  <a href="https://www.adivaha.com/onboard-meta.html" target="_blank" style="font-weight:600;gap: 7px;display: flex;align-items: center;color: #2196F3;">Register Your Busniess</a></div>
            <div style="gap: 7px;display:flex;flex-direction: row;justify-content: space-between;width: 100%"><p>Obtain your Partner ID (PID): WhatsApp us at  <a href="https://web.whatsapp.com/send?phone=+917303443889&text=Kindly+share+the+registration+link" style="font-weight:600;gap: 7px;display: flex;align-items: center;color: #000"><i class="fa-brands fa-whatsapp wh-blink"></i> +91 7303443889</a>- Availability: 11 AM - 5 PM (IST)</p><div class="ribbion"> <a href="https://youtu.be/3Bo75ajBIxA" target="_blank"> <span class="fa fa-play playbtn" style="    color: #fff;font-size: 49px;background: #F44336;width: 90px;height: 90px;border-radius: 100px;display: flex;align-items: center;justify-content: center;"> <i class="ripple"></i></span> </a> </div></div>
			
          </div>
          </div>
          <div class="col-xl-12 col-sm-6 mb-xl-0 mb-4">
            <div class="px-0 pb-2">
              <div class="table-responsive p-0">
                <table class="table align-items-center mb-0">
                  <tbody>
                    <tr>
                      <td>
                        <div class="d-flex py-1">
                          <div class="d-flex flex-column justify-content-center">
                            <h6 class="mb-0 text-sm">Your PID</h6>
                            <p class="text-xs text-secondary mb-0">Enter your Partner ID</p>
                          </div>
                        </div>
                      </td>
                      <td>
                        <div class="input-group input-group-outline <?php echo ($user_pid != "") ? 'is-filled' : '' ?>">
                          <label class="form-label">Your Partner ID</label>
                          <input type="text" class="form-control" name="pid" id="pid" value="<?php echo ($user_pid) ? $user_pid : '' ?>" required="true" onFocus="focused(this)" onfocusout="defocused(this)">
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="col-xl-12 col-sm-6 mb-xl-0 mb-4">
            <ul class="navbar-nav  justify-content-start  py-1" style="flex-direction:row;float:right">
              <?php
              if ($user_pid == "") { ?>
                <input type="hidden" name="action" value="addUser">
                <li class="nav-item d-flex">
                  <input type="submit" class="btn bg-gradient-primary mb-0 customebtn" name="verificationSubmit" id="connect" value="Connect">
                </li>
              <?php } else { ?>
                <input type="hidden" name="action" value="deleteUser">
                <li class="nav-item d-flex">
                  <input type="submit" class="btn bg-gradient-primary mb-0 customebtn" id="disconnect" name="disconnectAccount" value="Disconnect">
                </li>
              <?php } ?>
            </ul>
          </div>
		  
		  
		  
        </div>
      </form>
    </div>
  </main>
  <div class="fixed-plugin ps">
    <div class="card shadow-lg">
      <div class="card-header pb-0 pt-3">
        <div class="float-start">
          <h5 class="mt-3 mb-0">Material UI Configurator</h5>
          <p>See our dashboard options.</p>
        </div>
        <div class="float-end mt-4">
          <button class="btn btn-link text-dark p-0 fixed-plugin-close-button"> <i class="material-icons">clear</i> </button>
        </div>
      </div>
      <hr class="horizontal dark my-1">
      <div class="card-body pt-sm-3 pt-0">
        <div>
          <h6 class="mb-0">Sidebar Colors</h6>
        </div>
        <a href="javascript:void(0)" class="switch-trigger background-color">
          <div class="badge-colors my-2 text-start"> <span class="badge filter bg-gradient-primary active" data-color="primary" onClick="sidebarColor(this)"></span> <span class="badge filter bg-gradient-dark" data-color="dark" onClick="sidebarColor(this)"></span> <span class="badge filter bg-gradient-info" data-color="info" onClick="sidebarColor(this)"></span> <span class="badge filter bg-gradient-success" data-color="success" onClick="sidebarColor(this)"></span> <span class="badge filter bg-gradient-warning" data-color="warning" onClick="sidebarColor(this)"></span> <span class="badge filter bg-gradient-danger" data-color="danger" onClick="sidebarColor(this)"></span> </div>
        </a>
        <div class="mt-3">
          <h6 class="mb-0">Sidenav Type</h6>
          <p class="text-sm">Choose between 2 different sidenav types.</p>
        </div>
        <div class="d-flex">
          <button class="btn bg-gradient-dark px-3 mb-2 active" data-class="bg-gradient-dark" onClick="sidebarType(this)">Dark</button>
          <button class="btn bg-gradient-dark px-3 mb-2 ms-2" data-class="bg-transparent" onClick="sidebarType(this)">Transparent</button>
          <button class="btn bg-gradient-dark px-3 mb-2 ms-2" data-class="bg-white" onClick="sidebarType(this)">White</button>
        </div>
        <p class="text-sm d-xl-none d-block mt-2">You can change the sidenav type just on desktop view.</p>
        <div class="mt-3 d-flex">
          <h6 class="mb-0">Navbar Fixed</h6>
          <div class="form-check form-switch ps-0 ms-auto my-auto">
            <input class="form-check-input mt-1 ms-auto" type="checkbox" id="navbarFixed" onClick="navbarFixed(this)" checked="true">
          </div>
        </div>
        <hr class="horizontal dark my-3">
        <div class="mt-2 d-flex">
          <h6 class="mb-0">Light / Dark</h6>
          <div class="form-check form-switch ps-0 ms-auto my-auto">
            <input class="form-check-input mt-1 ms-auto" type="checkbox" id="dark-version" onClick="darkMode(this)">
          </div>
        </div>
        <hr class="horizontal dark my-sm-4">
        <div class="w-100 text-center"> <span></span>
          <h6 class="mt-3">Thank you for sharing!</h6>
          <a href="" class="btn btn-dark mb-0 me-2" target="_blank"> <i class="fab fa-twitter me-1" aria-hidden="true"></i> Tweet </a> <a href="" class="btn btn-dark mb-0 me-2" target="_blank"> <i class="fab fa-facebook-square me-1" aria-hidden="true"></i> Share </a>
        </div>
      </div>
    </div>
  </div>
  <script src="<?php echo ADIVAHA__PLUGIN_URL; ?>/asset/js/bootstrap.min.js"></script>
  <script src="<?php echo ADIVAHA__PLUGIN_URL; ?>/asset/js/perfect-scrollbar.min.js"></script>
  <script src="<?php echo ADIVAHA__PLUGIN_URL; ?>/asset/js/smooth-scrollbar.min.js"></script>
  <script src="<?php echo ADIVAHA__PLUGIN_URL; ?>/asset/js/buttons.js"></script>
  <script src="<?php echo ADIVAHA__PLUGIN_URL; ?>/asset/js/material-dashboard.min.js"></script>
  <style>
  .wh-blink {
       animation: blink 1s infinite;
    font-size: 23px;
    color: #8BC34A;
  }

  @keyframes blink {
    0%, 49% { opacity: 1; }
    50%, 100% { opacity: 0; }
  }
</style>
  <script>
    function openCity(evt, cityName) {
      var i, tabcontent, tablinks;
      tabcontent = document.getElementsByClassName("tabcontent");
      for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
      }
      tablinks = document.getElementsByClassName("tablinks");
      for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
      }
      document.getElementById(cityName).style.display = "block";
      evt.currentTarget.className += " active";
    }

    // Get the element with id="defaultOpen" and click on it
    document.getElementById("defaultOpen").click();
  </script>
  <script>
    deleteUser();
    function deleteUser() {
      jQuery('#disconnect').click(function(e) {
        e.preventDefault();

        swal({
            title: "Are you sure?",
            text: "Really, Do you want to disconnect your account?",
            icon: "warning",
            buttons: {
              'cancel': 'No',
              'danger': 'Yes'
            },
            dangerMode: true,
          })
          .then((willDelete) => {
            if (willDelete) {
              var frm_data = jQuery('#general-settings').serialize();
              jQuery.ajax({
                type: 'POST',
                url: '<?php echo ADIVAHA__PLUGIN_SITE_URL ?>/wp-admin/admin-ajax.php',
                data: frm_data,
                success: function(data) {
                  if (data == 1) {
                    // alert("Now your are disconnected!!");location.reload();
                    swal("Good job!", "Now your are disconnected!!", {
                      icon: "success",
                    }).then((value) => {
                      location.reload();
                    });
                  } else {
                    // alert("Sorry!! Try again later.");location.reload();
                    swal("Sorry!!", "Try again later.").then((value) => {
                      location.reload();
                    });
                  }
                },
                error: function(errorThrown) {
                  // alert("Some Error Occured. Contact to the Developer Team.");location.reload();
                  swal("Sorry!", "Some Error Occured. Contact to the Developer Team.", "error").then((value) => {
                    location.reload();
                  });
                }
              });
            } else {
              swal("Yipee!", "You are still connected.").then((value) => {
                location.reload();
              });
            }
          });
      });
    }

    addUser();
    function addUser() {
      jQuery('#connect').click(function(e) {
        e.preventDefault();
        var frm_data = jQuery('#general-settings').serialize();
        jQuery.ajax({
          type: 'POST',
          url: '<?php echo ADIVAHA__PLUGIN_SITE_URL ?>/wp-admin/admin-ajax.php',
          data: frm_data,
          success: function(data) {
            if (data == 1) {
              // alert("Now your are Connected!!");location.reload();
              swal("Good job!", "Now your are Connected!!", "success").then((value) => {
                location.reload();
              });
            } else {
              // alert("Sorry!! Try again later.");location.reload();
              swal("Sorry!", "Process failed due to some issue!", "error").then((value) => {
                location.reload();
              });
            }
          },
          error: function(errorThrown) {
            // alert("Some Error Occured. Contact to the Developer Team.");location.reload();
            swal("Sorry!", "Some Error Occured. Contact to the Developer Team.", "error").then((value) => {
              location.reload();
            });
          }
        });
      });
    }

    var Tawk_API = Tawk_API || {},
      Tawk_LoadStart = new Date();
    (function() {
      var s1 = document.createElement("script"),
        s0 = document.getElementsByTagName("script")[0];
      s1.async = true;
      s1.src = 'https://embed.tawk.to/616a912f86aee40a5736dbfe/1fi44e8ra';
      s1.charset = 'UTF-8';
      s1.setAttribute('crossorigin', '*');
      s0.parentNode.insertBefore(s1, s0);

      window.Tawk_API.onPrechatSubmit = function(data) {
        //place your code here
        console.log(data[0].answer);
        var my_data = {
          gtouch_name: data[0].answer,
          email: data[1].answer,
          action_from: "chat",
          your_isd: $("#gtouch_isd").val(),
          your_phone: data[2].answer,
          your_message: "Free Plugin Online Chat - " + data[3].answer,
          action: "my_register"
        };

        $.ajax({
          type: "post",
          data: my_data,
          url: "https://www.adivaha.com/custom_ajax.php",
          crossDomain: true,
          success: function(data) {
            alert("Saved");
            // window.location.href = "https://www.adivaha.com/thanks.html?d=" + email + "&p=" + gtouch_name;
          }
        });
      };

      window.Tawk_API.onOfflineSubmit = function(data) {
        //place your code here
        console.log(data);
        //var data = JSON.stringify(data);
        var questions = data.questions;
        var my_data = {
          gtouch_name: questions[0].answer,
          email: questions[1].answer,
          action_from: "chat",
          your_isd: $("#gtouch_isd").val(),
          your_phone: questions[2].answer,
          your_message: "Free Plugin Offline Chat - " + questions[3].answer,
          action: "my_register"
        };

        $.ajax({
          type: "post",
          data: my_data,
          url: "https://www.adivaha.com/custom_ajax.php",
          crossDomain: true,
          success: function(data) {
            alert("Saved");
            // window.location.href = "https://www.adivaha.com/thanks.html?d=" + email + "&p=" + gtouch_name;
          }
        });
      };
    })();
  </script>