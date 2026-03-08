function openImportExcel(){
  document.getElementById('import-result').style.display='none';
  openModal('modal-import-excel');
}
function isValidName(n){if(!n||n.length<3)return false;var bad=['المجموع','الإجمالي','Total','Sum','إجمالي','مجموع','العدد','المبلغ','الرصيد','ريال'];for(var i=0;i<bad.length;i++){if(n.indexOf(bad[i])!==-1)return false;}if(/^\d+$/.test(n))return false;if(/^05\d{8}$/.test(n))return false;return true;}
function detectPayment(r){var a=parseFloat(r['المبلغ']||r['Amount']||r['المدفوع']||0);var st=r['الحالة']||r['Status']||'';if(a>0)return {paid:true,amount:a};if(st){var s=st.toLowerCase();if(s.indexOf('مدفوع')!==-1||s.indexOf('paid')!==-1)return {paid:true,amount:a||500};if(s.indexOf('متأخر')!==-1||s.indexOf('لم يدفع')!==-1)return {paid:false,amount:0};}return {paid:false,amount:0};}
function findMember(n){for(var i=0;i<State.getMembers().length;i++){var m=State.getMembers()[i];if(m.name.toLowerCase()===n.toLowerCase())return m;var n1=n.toLowerCase().replace(/^(أبو|أبي|أم|ابن|بن)\s+/g,'');var n2=m.name.toLowerCase().replace(/^(أبو|أبي|أم|ابن|بن)\s+/g,'');if(n1===n2)return m;}return null;}

document.getElementById('excel-import-input').onchange=function(){
  var file=this.files[0];
  if(!file){return;}
  var reader=new FileReader();
  reader.onload=function(e){
    try{
      var data=new Uint8Array(e.target.result);
      var wb=XLSX.read(data,{type:'array'});
      var ws=wb.Sheets[wb.SheetNames[0]];
      var json=XLSX.utils.sheet_to_json(ws);
      if(!json.length){toast('الملف فارغ','error');return;}
      
      var total=0,valid=0,skip=0,newM=0,upd=0,paid=0,unpaid=0;
      var skipped=[];
      
      json.forEach(function(row){
        var name=(row['الاسم']||row['Name']||row['name']||'').trim();
        total++;
        
        if(!isValidName(name)){
          skip++;
          skipped.push(name);
          return;
        }
        
        valid++;
        var pay=detectPayment(row);
        var member=findMember(name);
        
        if(member){
          upd++;
          if(row['الجوال'])member.phone=row['الجوال'];
          if(row['رقم الهوية'])member.idNum=row['رقم الهوية'];
          if(pay.paid){
            paid++;
            if(!member.payments)member.payments=[];
            member.payments.push({amount:pay.amount,date:today(),imported:true});
          }else{unpaid++;}
        }else{
          newM++;
          var newMember={
            id:uid(),
            name:name,
            phone:row['الجوال']||'',
            idNum:row['رقم الهوية']||'',
            family:'العوامي',
            joinDate:today(),
            status:'مشترك',
            type:'عادي',
            notes:'مستورد',
            payments:pay.paid?[{amount:pay.amount,date:today(),imported:true}]:[]
          };
          State.getMembers().push(newMember);
          if(pay.paid){paid++;}else{unpaid++;}
        }
      });
      
      saveDB();
      
      var html='<div style="background:#dcfce7;border:1px solid #86efac;border-radius:10px;padding:14px">';
      html+='<div style="font-weight:700;color:#166534;margin-bottom:10px">✅ تم المعالجة</div>';
      html+='<div style="font-size:12px;color:#166534">📊 السجلات: '+total+' • ✅ صحيح: '+valid+' • ⚠️ متجاهل: '+skip+'<br>';
      html+='➕ جديد: '+newM+' • 🔄 محدث: '+upd+'<br>';
      html+='💰 دفعوا: '+paid+' • ⏳ لم يدفعوا: '+unpaid+'</div>';
      if(skip>0){
        html+='<div style="margin-top:8px;padding:8px;background:#fff;border-radius:6px;font-size:11px;max-height:60px;overflow:auto">';
        html+='<b>متجاهل:</b> '+skipped.join(', ')+'</div>';
      }
      html+='</div>';
      
      document.getElementById('import-result').style.display='block';
      document.getElementById('import-result').innerHTML=html;
      toast('تم معالجة '+valid+' سجل ✅');
      renderMembers();
    }catch(err){toast('خطأ في قراءة الملف','error');console.error(err);}
  };
  reader.readAsArrayBuffer(file);
};

// =====================================================
// Drag & Drop Enhancement for AI Upload
// =====================================================
(function() {
  function setupDragDrop() {
    var uploadArea = document.getElementById('upload-area');
    if (!uploadArea) return;
    
    uploadArea.addEventListener('dragover', function(e) {
      e.preventDefault();
      e.stopPropagation();
      this.style.borderColor = 'var(--green)';
      this.style.backgroundColor = '#f0fdf4';
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
      e.preventDefault();
      e.stopPropagation();
      this.style.borderColor = 'var(--border)';
      this.style.backgroundColor = 'transparent';
    });
    
    uploadArea.addEventListener('drop', function(e) {
      e.preventDefault();
      e.stopPropagation();
      this.style.borderColor = 'var(--border)';
      this.style.backgroundColor = 'transparent';
      
      var files = e.dataTransfer.files;
      if (files.length > 0) {
        var fileInput = document.getElementById('file-input-ai');
        fileInput.files = files;
        handleAIFileUpload({target: {files: files}});
      }
    });
  }
  
  // Setup on page load and page switches
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupDragDrop);
  } else {
    setupDragDrop();
  }
})();
