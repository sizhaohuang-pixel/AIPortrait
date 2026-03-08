try:  
  open('app/api/controller/Portrait.php', 'r', encoding='utf-8').read()  
  print('UTF-8')  
except Exception as e:  
  print('NOT UTF-8', e)  
