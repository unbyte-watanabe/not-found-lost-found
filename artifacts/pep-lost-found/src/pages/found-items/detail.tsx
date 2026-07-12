import React from "react";
import { useLocation, useParams } from "wouter";
import { ArrowLeft, PackageSearch, Trash2, Calendar, MapPin, CheckCircle, PackageCheck, AlertTriangle } from "lucide-react";
import { useGetFoundItem, useUpdateFoundItemStatus, useDeleteFoundItem, FoundItemStatus } from "@workspace/api-client-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { formatDateTime, getStatusColor, getCategoryColor, MaskedText } from "@/lib/format";
import { useToast } from "@/hooks/use-toast";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";

export default function FoundItemDetail() {
  const [, setLocation] = useLocation();
  const { id } = useParams<{ id: string }>();
  const { toast } = useToast();
  
  const { data: item, isLoading } = useGetFoundItem(id || "", {
    query: { enabled: !!id }
  });

  const updateStatus = useUpdateFoundItemStatus();
  const deleteItem = useDeleteFoundItem();

  const [returnDialogOpen, setReturnDialogOpen] = React.useState(false);
  const [returnTo, setReturnTo] = React.useState("");
  const [identityVerified, setIdentityVerified] = React.useState(false);
  const [receiptSigned, setReceiptSigned] = React.useState(false);

  const handleReturn = async () => {
    if (!id) return;
    try {
      await updateStatus.mutateAsync({
        id,
        data: {
          status: "返還済",
          returnedTo: returnTo,
          identityVerified,
          receiptSigned
        }
      });
      toast({ title: "返還処理が完了しました" });
      setReturnDialogOpen(false);
    } catch (e) {
      toast({ title: "エラーが発生しました", variant: "destructive" });
    }
  };

  const handleStatusChange = async (status: FoundItemStatus) => {
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

  const handleDelete = async () => {
    if (!id) return;
    try {
      await deleteItem.mutateAsync({ id });
      toast({ title: "データを削除しました" });
      setLocation("/found-items");
    } catch (e) {
      toast({ title: "削除に失敗しました", variant: "destructive" });
    }
  };

  if (isLoading) {
    return <div className="space-y-6"><Skeleton className="h-10 w-48" /><Skeleton className="h-[400px] w-full rounded-xl" /></div>;
  }

  if (!item) {
    return <div className="text-center py-20">データが見つかりません</div>;
  }

  return (
    <div className="max-w-4xl mx-auto space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div className="flex items-center gap-4">
          <Button variant="ghost" size="icon" onClick={() => setLocation("/found-items")}>
            <ArrowLeft className="w-5 h-5" />
          </Button>
          <div>
            <div className="flex items-center gap-2 mb-1">
              <h1 className="text-2xl font-bold">拾得物詳細</h1>
              <Badge variant="outline" className={`ml-2 ${getStatusColor(item.status)}`}>
                {item.status}
              </Badge>
            </div>
            <p className="text-sm font-mono text-muted-foreground">管理番号: {item.managementNo}</p>
          </div>
        </div>
        
        {/* Actions based on status */}
        <div className="flex gap-2 self-start sm:self-auto">
          {item.status === "保管中" && (
            <>
              <Dialog open={returnDialogOpen} onOpenChange={setReturnDialogOpen}>
                <DialogTrigger asChild>
                  <Button className="bg-green-600 hover:bg-green-700 text-white shadow-sm">
                    <CheckCircle className="w-4 h-4 mr-2" />
                    持ち主に返還
                  </Button>
                </DialogTrigger>
                <DialogContent>
                  <DialogHeader>
                    <DialogTitle>返還手続き</DialogTitle>
                    <DialogDescription>持ち主へ返還する前の確認事項をチェックしてください。</DialogDescription>
                  </DialogHeader>
                  <div className="space-y-4 py-4">
                    <div className="space-y-2">
                      <label className="text-sm font-medium">受取人氏名</label>
                      <Input value={returnTo} onChange={e => setReturnTo(e.target.value)} placeholder="例: 山田 太郎" />
                    </div>
                    <div className="flex items-start space-x-3 p-3 border rounded-md">
                      <Checkbox id="identity" checked={identityVerified} onCheckedChange={(c) => setIdentityVerified(!!c)} />
                      <div className="space-y-1 leading-none">
                        <label htmlFor="identity" className="text-sm font-medium leading-none cursor-pointer">身分証明書の確認</label>
                        <p className="text-sm text-muted-foreground">免許証、保険証などで本人確認を行いました。</p>
                      </div>
                    </div>
                    <div className="flex items-start space-x-3 p-3 border rounded-md">
                      <Checkbox id="receipt" checked={receiptSigned} onCheckedChange={(c) => setReceiptSigned(!!c)} />
                      <div className="space-y-1 leading-none">
                        <label htmlFor="receipt" className="text-sm font-medium leading-none cursor-pointer">受領書のサイン</label>
                        <p className="text-sm text-muted-foreground">タブレットまたは紙の受領書にサインをもらいました。</p>
                      </div>
                    </div>
                  </div>
                  <DialogFooter>
                    <Button variant="outline" onClick={() => setReturnDialogOpen(false)}>キャンセル</Button>
                    <Button onClick={handleReturn} disabled={!returnTo || !identityVerified || !receiptSigned || updateStatus.isPending}>
                      返還を完了する
                    </Button>
                  </DialogFooter>
                </DialogContent>
              </Dialog>

              <Button variant="outline" onClick={() => handleStatusChange("警察提出済")} disabled={updateStatus.isPending}>
                警察提出
              </Button>
            </>
          )}

          <Dialog>
            <DialogTrigger asChild>
              <Button variant="ghost" size="icon" className="text-destructive hover:bg-destructive/10">
                <Trash2 className="w-5 h-5" />
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>データの削除</DialogTitle>
                <DialogDescription>
                  この拾得物データを完全に削除します。この操作は取り消せません。
                </DialogDescription>
              </DialogHeader>
              <DialogFooter>
                <Button variant="outline">キャンセル</Button>
                <Button variant="destructive" onClick={handleDelete} disabled={deleteItem.isPending}>削除する</Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {/* Left Column: Image & Quick Info */}
        <div className="space-y-6">
          <Card className="border-border/50 shadow-sm overflow-hidden">
            <div className="aspect-square bg-secondary/30 relative flex items-center justify-center p-4">
              {item.imageUrl ? (
                <img src={item.imageUrl} alt="拾得物" className="w-full h-full object-contain rounded-md" />
              ) : (
                <PackageSearch className="w-20 h-20 text-muted-foreground/30" />
              )}
            </div>
            <CardContent className="p-4 bg-card">
              <div className="space-y-3">
                <div>
                  <div className="text-xs text-muted-foreground mb-1">カテゴリ</div>
                  <div className="flex gap-2">
                    <Badge variant="outline" className={getCategoryColor(item.category)}>{item.category}</Badge>
                    {item.subCategory && <Badge variant="secondary">{item.subCategory}</Badge>}
                  </div>
                </div>
                <div>
                  <div className="text-xs text-muted-foreground mb-1">保管場所</div>
                  <div className="font-medium flex items-center gap-2">
                    <PackageCheck className="w-4 h-4 text-primary" />
                    {item.storageLocation || "未設定"}
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Right Column: Details */}
        <div className="md:col-span-2 space-y-6">
          <Card className="border-border/50 shadow-sm">
            <CardHeader className="pb-3 border-b border-border/50">
              <CardTitle className="text-lg">詳細情報</CardTitle>
            </CardHeader>
            <CardContent className="p-6 space-y-6">
              <div>
                <div className="text-sm font-medium text-muted-foreground mb-2 flex items-center gap-2">
                  <PackageSearch className="w-4 h-4" /> 特徴
                </div>
                <p className="text-foreground leading-relaxed whitespace-pre-wrap">
                  {item.features || "記載なし"}
                </p>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                  <div className="text-sm font-medium text-muted-foreground mb-2 flex items-center gap-2">
                    <Calendar className="w-4 h-4" /> 拾得日時
                  </div>
                  <p className="text-foreground font-medium">{formatDateTime(item.foundDatetime)}</p>
                </div>
                <div>
                  <div className="text-sm font-medium text-muted-foreground mb-2 flex items-center gap-2">
                    <MapPin className="w-4 h-4" /> 拾得場所
                  </div>
                  <p className="text-foreground font-medium">{item.foundLocation || "記載なし"}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="border-border/50 shadow-sm">
            <CardHeader className="pb-3 border-b border-border/50">
              <CardTitle className="text-lg">拾得者情報</CardTitle>
            </CardHeader>
            <CardContent className="p-6">
              {item.finderInfo ? (
                <div className="space-y-4">
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <div className="text-sm text-muted-foreground mb-1">氏名</div>
                      <MaskedText text={item.finderInfo.name} />
                    </div>
                    <div>
                      <div className="text-sm text-muted-foreground mb-1">連絡先</div>
                      <MaskedText text={item.finderInfo.contact} />
                    </div>
                  </div>
                  {item.finderInfo.rightsWaived && (
                    <div className="flex items-center gap-2 text-sm text-amber-700 bg-amber-50 p-3 rounded-md border border-amber-200">
                      <AlertTriangle className="w-4 h-4 shrink-0" />
                      <span>拾得者の権利（報労金等）は放棄されています。</span>
                    </div>
                  )}
                </div>
              ) : (
                <p className="text-muted-foreground text-sm">拾得者情報の登録はありません。</p>
              )}
            </CardContent>
          </Card>

          {item.status === "返還済" && (
            <Card className="border-green-200 shadow-sm bg-green-50/50">
              <CardHeader className="pb-3 border-b border-green-200">
                <CardTitle className="text-lg text-green-800 flex items-center gap-2">
                  <CheckCircle className="w-5 h-5" /> 返還記録
                </CardTitle>
              </CardHeader>
              <CardContent className="p-6">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <div className="text-sm text-green-700/70 mb-1">受取人</div>
                    <div className="font-medium text-green-900">{item.returnedTo || "不明"}</div>
                  </div>
                  <div>
                    <div className="text-sm text-green-700/70 mb-1">返還日時</div>
                    <div className="font-medium text-green-900">{formatDateTime(item.returnedAt)}</div>
                  </div>
                  <div>
                    <div className="text-sm text-green-700/70 mb-1">確認事項</div>
                    <div className="flex gap-2 mt-1">
                      {item.identityVerified && <Badge variant="outline" className="bg-green-100 text-green-800 border-green-300">本人確認済</Badge>}
                      {item.receiptSigned && <Badge variant="outline" className="bg-green-100 text-green-800 border-green-300">受領書有</Badge>}
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          )}
        </div>
      </div>
    </div>
  );
}