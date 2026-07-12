import React, { useState } from "react";
import { Download, FileText, AlertCircle, Calendar } from "lucide-react";
import { useExportFoundItemsForPolice } from "@workspace/api-client-react";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { useToast } from "@/hooks/use-toast";

export default function ExportPage() {
  const { toast } = useToast();
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateFromDateTo] = useState("");
  const [isExporting, setIsExporting] = useState(false);

  // We use the fetch function directly from the generated code, but trigger it manually
  // so we can handle the file download blob.
  const handleExport = async () => {
    try {
      setIsExporting(true);
      
      const params = new URLSearchParams();
      if (dateFrom) params.append("dateFrom", new Date(dateFrom).toISOString());
      if (dateTo) params.append("dateTo", new Date(dateTo).toISOString());
      
      const url = `/api/found-items/export/police${params.toString() ? `?${params.toString()}` : ''}`;
      
      const response = await fetch(url);
      if (!response.ok) throw new Error("エクスポートに失敗しました");
      
      const blob = await response.blob();
      const downloadUrl = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = downloadUrl;
      
      // Format current date for filename
      const today = new Date().toISOString().split('T')[0];
      a.download = `police_export_${today}.csv`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(downloadUrl);
      document.body.removeChild(a);
      
      toast({ title: "CSVファイルのダウンロードを開始しました" });
    } catch (e) {
      toast({ title: "エクスポートに失敗しました", variant: "destructive" });
    } finally {
      setIsExporting(false);
    }
  };

  return (
    <div className="space-y-6 max-w-3xl mx-auto animate-in fade-in slide-in-from-bottom-4 duration-500">
      <div>
        <h1 className="text-2xl md:text-3xl font-bold text-foreground">警察提出用データ出力</h1>
        <p className="text-muted-foreground mt-1 text-sm md:text-base">
          一定期間保管し、持ち主が現れなかった拾得物を警察へ提出するためのCSVデータを出力します。
        </p>
      </div>

      <Card className="border-border/50 shadow-sm overflow-hidden">
        <CardHeader className="bg-secondary/20 border-b border-border/50">
          <CardTitle className="flex items-center gap-2">
            <FileText className="w-5 h-5 text-primary" />
            CSVエクスポート設定
          </CardTitle>
          <CardDescription>
            出力する拾得物の期間（拾得日）を指定してください。指定がない場合は全データが出力されます。
          </CardDescription>
        </CardHeader>
        <CardContent className="p-6 space-y-6">
          <Alert className="bg-amber-50 text-amber-800 border-amber-200">
            <AlertCircle className="w-4 h-4 text-amber-600" />
            <AlertDescription className="text-amber-800 ml-2">
              出力されるデータには、個人情報が含まれる場合があります。ファイルの取り扱いには十分注意してください。
            </AlertDescription>
          </Alert>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div className="space-y-2">
              <label className="text-sm font-medium flex items-center gap-2">
                <Calendar className="w-4 h-4 text-muted-foreground" />
                期間（開始）
              </label>
              <Input 
                type="date" 
                value={dateFrom} 
                onChange={(e) => setDateFrom(e.target.value)} 
                className="bg-background"
              />
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium flex items-center gap-2">
                <Calendar className="w-4 h-4 text-muted-foreground" />
                期間（終了）
              </label>
              <Input 
                type="date" 
                value={dateTo} 
                onChange={(e) => setDateFromDateTo(e.target.value)} 
                className="bg-background"
              />
            </div>
          </div>

          <div className="pt-4 border-t border-border/50 flex justify-end">
            <Button 
              size="lg" 
              onClick={handleExport} 
              disabled={isExporting}
              className="gap-2 shadow-sm hover-elevate px-8"
            >
              <Download className="w-4 h-4" />
              {isExporting ? "出力中..." : "CSVダウンロード"}
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}