function verify(){
    var key=document.getElementById('consumer-secret').value;
    var value=document.getElementById('consumer-key').value;     
    if(key.length>10 && value.length >10){
       document.getElementById('submit-key').disabled = false;
    }else{
      document.getElementById('submit-key').disabled = true;
    }
}