import React from "react";
import { useLocation, useParams } from "wouter";
import { ArrowLeft, User, Calendar, MapPin, SearchX, CheckCircle, PackageSearch } from "lucide-react";
import { useGetLostReport, useUpdateLostReportStatus, LostReportStatus, useListMatches } from "@workspace/api-client-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import { formatDateTime, formatDate, getStatusColor, getCategoryColor, MaskedText, getMatchScoreColor, getMatchScoreBgColor } from "@/lib/format";
import { useToast } from "@/hooks/use-toast";
import { Progress } from "@/components/ui/progress";

export default function LostReportDetail() {
  const [, setLocation] = useLocation();
  const { id } = useParams<{ id: string }>();
  const { toast } = useToast();
  
  const { data: report, isLoading: reportLoading } = useGetLostReport(id || "", {
    query: { enabled: !!id }
  });

  const { data: matchesData, isLoading: matchesLoading } = useListMatches(
    { lostReportId: id },
    { query: { enabled: !!id } }
  );

  const updateStatus = useUpdateLostReportStatus();

  const handleStatusChange = async (status: LostReportStatus) => {
    if (!id) return;
    try {
      await updateStatus.mutateAsync({
        id,
        data: { status }
      });
      toast({ title: "ステータスを更新しました" });
    } catch (e) {
      toast({ title: "エラーが発生しました", variant: "destructive" });
    }
  };

  if (reportLoading) {
    return <div className="space-y-6"><Skeleton className="h-10 w-48" /><Skeleton className="h-[400px] w-full rounded-xl" /></div>;
  }

  if (!report) {
    return <div className="text-center py-20">データが見つかりません</div>;
  }

  return (
    <div className="max-w-4xl mx-auto space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div className="flex items-center gap-4">
          <Button variant="ghost" size="icon" onClick={() => setLocation("/lost-reports")}>
            <ArrowLeft className="w-5 h-5" />
          </Button>
          <div>
            <div className="flex items-center gap-2 mb-1">
              <h1 className="text-2xl font-bold">紛失届詳細</h1>
              <Badge variant="outline" className={`ml-2 ${getStatusColor(report.status)}`}>
                {report.status}
              </Badge>
            </div>
            <p className="text-sm text-muted-foreground">受付: {formatDateTime(report.createdAt)}</p>
          </div>
        </div>
        
        {/* Actions based on status */}
        <div className="flex gap-2 self-start sm:self-auto">
          {report.status === "探索中" && (
            <>
              <Button className="bg-green-600 hover:bg-green-700 text-white shadow-sm" onClick={() => handleStatusChange("解決済")} disabled={updateStatus.isPending}>
                <CheckCircle className="w-4 h-4 mr-2" />
                解決済にする
              </Button>
              <Button variant="outline" onClick={() => handleStatusChange("キャンセル")} disabled={updateStatus.isPending}>
                キャンセル
              </Button>
            </>
          )}
          {report.status !== "探索中" && (
            <Button variant="outline" onClick={() => handleStatusChange("探索中")} disabled={updateStatus.isPending}>
              探索中に戻す
            </Button>
          )}
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Card className="border-border/50 shadow-sm">
          <CardHeader className="pb-3 border-b border-border/50">
            <CardTitle className="text-lg">紛失物の情報</CardTitle>
          </CardHeader>
          <CardContent className="p-6 space-y-6">
            <div>
              <div className="text-xs text-muted-foreground mb-1">カテゴリ</div>
              <Badge variant="outline" className={getCategoryColor(report.category)}>{report.category}</Badge>
            </div>
            <div>
              <div className="text-sm font-medium text-muted-foreground mb-2">特徴</div>
              <p className="text-foreground leading-relaxed whitespace-pre-wrap">
                {report.features || "記載なし"}
              </p>
            </div>

            <div className="space-y-4 pt-2 border-t border-border/50">
              <div>
                <div className="text-sm font-medium text-muted-foreground mb-1 flex items-center gap-2">
                  <Calendar className="w-4 h-4" /> 紛失したと思われる日時
                </div>
                <p className="text-sm font-medium">
                  {report.lostDatetimeFrom ? formatDateTime(report.lostDatetimeFrom) : "不明"}
                  {report.lostDatetimeTo && ` 〜 ${formatDateTime(report.lostDatetimeTo)}`}
                </p>
              </div>
              <div>
                <div className="text-sm font-medium text-muted-foreground mb-1 flex items-center gap-2">
                  <MapPin className="w-4 h-4" /> 紛失したと思われる場所
                </div>
                <p className="text-sm font-medium">{report.lostLocationEstimated || "不明"}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="border-border/50 shadow-sm h-fit">
          <CardHeader className="pb-3 border-b border-border/50 bg-secondary/20">
            <CardTitle className="text-lg flex items-center gap-2">
              <User className="w-5 h-5 text-primary" /> お客様情報
            </CardTitle>
          </CardHeader>
          <CardContent className="p-6 space-y-4">
            <div>
              <div className="text-sm text-muted-foreground mb-1">お名前</div>
              <div className="font-medium text-lg"><MaskedText text={report.ownerName} /></div>
            </div>
            <div>
              <div className="text-sm text-muted-foreground mb-1">連絡先</div>
              <div className="font-medium"><MaskedText text={report.ownerContact} /></div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Matches Section */}
      <div className="space-y-4 mt-8">
        <h2 className="text-xl font-bold border-b pb-2">システムによる類似候補</h2>
        
        {matchesLoading ? (
          <Skeleton className="h-32 w-full rounded-xl" />
        ) : matchesData && matchesData.length > 0 ? (
          <div className="grid grid-cols-1 gap-4">
            {matchesData.map((match) => (
              <Card key={match.id} className={`border ${match.score >= 70 ? 'border-green-200 shadow-sm' : 'border-border/50'} overflow-hidden`}>
                <div className="flex flex-col md:flex-row">
                  <div className={`p-4 md:w-[120px] flex flex-col items-center justify-center border-b md:border-b-0 md:border-r border-border/50 ${getMatchScoreBgColor(match.score)}`}>
                    <div className="text-xs font-bold mb-1 opacity-80">一致度</div>
                    <div className={`text-3xl font-black ${getMatchScoreColor(match.score)}`}>{match.score}%</div>
                  </div>
                  
                  <div className="flex-1 p-4 flex flex-col md:flex-row gap-4">
                    <div className="w-20 h-20 rounded-md bg-secondary/50 border border-border overflow-hidden shrink-0 flex items-center justify-center">
                      {match.foundItem.imageUrl ? (
                        <img src={match.foundItem.imageUrl} alt="拾得物" className="w-full h-full object-cover" />
                      ) : (
                        <PackageSearch className="w-8 h-8 text-muted-foreground/30" />
                      )}
                    </div>
                    
                    <div className="flex-1 space-y-2">
                      <div className="flex justify-between items-start">
                        <div>
                          <div className="flex gap-2 items-center mb-1">
                            <span className="font-mono text-xs text-muted-foreground">{match.foundItem.managementNo}</span>
                            <Badge variant="outline" className={`text-[10px] py-0 px-2 ${getStatusColor(match.foundItem.status)}`}>
                              {match.foundItem.status}
                            </Badge>
                          </div>
                          <p className="font-medium line-clamp-1">{match.foundItem.features}</p>
                        </div>
                        <Button variant="outline" size="sm" onClick={() => setLocation(`/found-items/${match.foundItem.id}`)}>
                          詳細を確認
                        </Button>
                      </div>
                      
                      <div className="text-xs text-muted-foreground">
                        <div className="flex flex-wrap gap-x-4 gap-y-1 mt-2">
                          <span>拾得日: {formatDate(match.foundItem.foundDatetime)}</span>
                          {match.foundItem.foundLocation && <span>場所: {match.foundItem.foundLocation}</span>}
                        </div>
                      </div>
                      
                      <div className="mt-3 pt-3 border-t border-border/30">
                        <p className="text-xs font-medium mb-1">AI 判定理由:</p>
                        <ul className="text-xs text-muted-foreground list-disc pl-4 space-y-1">
                          {match.reasons.map((reason, i) => (
                            <li key={i}>{reason}</li>
                          ))}
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </Card>
            ))}
          </div>
        ) : (
          <Card className="p-8 text-center border-dashed border-border/60 bg-secondary/10">
            <SearchX className="w-10 h-10 text-muted-foreground/40 mx-auto mb-3" />
            <p className="text-muted-foreground">現在、類似する拾得物は見つかっていません。</p>
            <p className="text-sm text-muted-foreground mt-1">拾得物が登録されると自動的にここに表示されます。</p>
          </Card>
        )}
      </div>
    </div>
  );
}