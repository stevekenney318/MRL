(function() {
    tinymce.create('embed.calculator.imsFmeSsc', {

        init : function(ed, url) {
          
            ed.addButton('deleteCalculator', {

                class: 'deleteCalculator',               
                cmd : 'deleteCalculator',              
       

            });    
           

            var removeDeleteButton=function(){
               alltext = tinyMCE.activeEditor.getContent();
        
               var selection='<p><button class="sscbtndel">Delete Calculator</button></p>';
               if(alltext.indexOf(selection)!=-1){
                    textcut=alltext.substr(0,alltext.indexOf(selection));
                    textcutafter=alltext.substr(alltext.indexOf(selection)+selection.length);
                    finaltext=textcut+textcutafter;
                    tinyMCE.activeEditor.setContent(finaltext);
                }

            }
            setTimeout(function(){

                removeDeleteButton();

            },1000)


      ed.onClick.add(function(editor, e) { 
        var nodename=e.target.nodeName;

        if(nodename.toUpperCase()=="BUTTON"){   
                     e.target.parentElement.previousSibling.remove();
                     e.target.parentElement.remove();

                    }else if(nodename.toUpperCase()=="SPAN"){

                        var nodename=e.target.previousSibling.nodeName;
                        if(nodename.toUpperCase()=="IFRAME"){
                         
                      var y = document.createElement('p');
                        // console.log("Delete button")
                      y.innerHTML = '<button class="sscbtndel">Delete Calculator</button>';
                   
                     
                     
                        e.target.previousSibling.parentElement.parentElement.parentElement.parentElement.parentElement.after(y)
                 

                       
                    }else{
                        removeDeleteButton();
                    }
                }else{

                    removeDeleteButton();
                }
          
        });

},
        // ... Hidden code
    });
    // Register plugin
    tinymce.PluginManager.add( 'imsFmeScc', embed.calculator.imsFmeSsc );
})();
