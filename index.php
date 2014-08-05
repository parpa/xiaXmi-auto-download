<meta name="charset" content="utf-8">
<meta charset="utf-8">
<script type="text/javascript" src="jquery.min.js"></script>
<script type="text/javascript">
$(function () {
	$('#main').hide();
    var downinfo = {img:'',mp3:'',name:'', songId:0, hasDown:false};
    $('#api').click(function () {
        var songId = 0;
        var result = $('#songId').val().trim().match(/\d+$/);
        if (result) {
            songId = parseInt(result[0]);
        }
        songId = songId ? songId : parseInt($('#songId').val().trim());
        if (songId<1) {
            alert('error id');
            return;
        }
        $('#songImg').attr('src', "./load.jpg");
        $('#main').hide();
        $.ajax({
          url: 'api.php',
          type: 'POST',
          dataType: 'json',
          data: {action: 'api', songId:songId},
          complete: function(xhr, textStatus) {
            //called when complete
          },
          success: function(data, textStatus, xhr) {
              if (data && data.song) {
                  data = data.song;
              } else {
                  alert('error');
                  return;
              }
            //called when successful
            downinfo.songId = data.song_id;
            downinfo.img=data.song_logo;
            downinfo.mp3=data.song_location;
            downinfo.name=data.song_name;
            downinfo.hasDown=data.hasDown;
            
            $('#songId').val(data.song_id);
            
            $('#songImg').attr('src', downinfo.img);
            $('#songTitle').text(downinfo.name);
            if (downinfo.hasDown) {
                $('#songHasDown').show();
            } else {
                $('#songHasDown').hide();
            }
            $('#main').show();
          },
          error: function(xhr, textStatus, errorThrown) {
            //called when there is an error
            $('#main').hide();
            alert('error');
          }
        });
        
    });
    $('#download').click(function () {
        $('#api').prop('disabled', true);
        $('#download').prop('disabled', true);
        $.ajax({
          url: 'api.php',
          type: 'POST',
          dataType: 'json',
          data: {action: 'download', songId:downinfo.songId},
          complete: function(xhr, textStatus) {
            //called when complete
            $('#download').prop('disabled', false);
            $('#api').prop('disabled', false);
          },
          success: function(data, textStatus, xhr) {
            //called when successful
            $('#songHasDown').show();
          },
          error: function(xhr, textStatus, errorThrown) {
            //called when there is an error
            alert('error down');
          }
        });
        
    });
});
</script>
<body id="" style="min-width:640px;">
    <div style="margin:100px auto;width:400px;text-align:center;">
        
    
    <form id="downloadForm" onsubmit="if(!$('#api').prop('disabled')){$('#api').click();}return false;" method="get" accept-charset="utf-8">
        <label for="songId">songId </label><input type="text" name="songId" value="" id="songId">    

        <input id="api" type="button" value="getinfo &rarr;">
    </form>
    <div id="main" style="text-align:center;">
        <img id="songImg" src="" style="width:185px;height:185px;" />
        <br>
        <span id="songTitle" ></span>
        <br>
        <span id="songHasDown" style="color:red;" >已经下载了</span>
        <br>
        <br>
        <input id="download" type="button" value="download &rarr;">
    </div>
    </div>
</body>

