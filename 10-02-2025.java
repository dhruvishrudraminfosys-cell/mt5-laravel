#property strict

input string api_urls = "http://192.168.29.82:8000/api/mt5/tick";

string url_list[];
string tick_buffer[];
int buffer_count = 0;

//+------------------------------------------------------------------+
int OnInit()
{
   ushort separator = StringGetCharacter(",",0);
   StringSplit(api_urls, separator, url_list);

   EventSetMillisecondTimer(100); // 🔥 SEND EVERY 100ms

   Print("Initialized Batch Tick Sender");
   return(INIT_SUCCEEDED);
}
//+------------------------------------------------------------------+
void OnDeinit(const int reason)
{
   EventKillTimer();
}
//+------------------------------------------------------------------+
void OnTick()
{
   double bid = SymbolInfoDouble(_Symbol, SYMBOL_BID);
   double ask = SymbolInfoDouble(_Symbol, SYMBOL_ASK);

   if(bid <= 0 || ask <= 0) return;

   int digits = (int)SymbolInfoInteger(_Symbol, SYMBOL_DIGITS);

   string json = StringFormat(
      "{\"symbol\":\"%s\",\"bid\":%.*f,\"ask\":%.*f,\"time\":%d}",
      _Symbol,digits,bid,digits,ask,(int)TimeCurrent()
   );

   ArrayResize(tick_buffer,buffer_count+1);
   tick_buffer[buffer_count]=json;
   buffer_count++;
}
//+------------------------------------------------------------------+
void OnTimer()
{
   if(buffer_count==0) return;

   string final_json="[";
   for(int i=0;i<buffer_count;i++)
   {
      final_json+=tick_buffer[i];
      if(i<buffer_count-1) final_json+=",";
   }
   final_json+="]";

   char post[];
   StringToCharArray(final_json,post);

   string headers="Content-Type: application/json\r\n";

   for(int i=0;i<ArraySize(url_list);i++)
   {
      string url=url_list[i];

      char result[];
      string result_headers;

      int res=WebRequest("POST",url,headers,5000,post,result,result_headers);

      if(res==-1)
         Print("Error:",GetLastError());
   }

   buffer_count=0;
   ArrayResize(tick_buffer,0);
}
