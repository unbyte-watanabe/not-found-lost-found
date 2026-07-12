import React, { useState } from "react";
import { Link } from "wouter";
import { Plus, Search, Filter, Calendar as CalendarIcon, User, SearchX } from "lucide-react";
import { useListLostReports, LostReportStatus, LostReportCategory } from "@workspace/api-client-react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import { Card } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { formatDateTime, formatDate, getStatusColor, getCategoryColor, MaskedText } from "@/lib/format";
import { useDebounce } from "@/hooks/use-debounce";

export default function LostReportsList() {
  const [search, setSearch] = useState("");
  const debouncedSearch = useDebounce(search, 500);
  const [status, setStatus] = useState<string>("探索中");
  const [category, setCategory] = useState<string>("all");

  const { data, isLoading } = useListLostReports({
    search: debouncedSearch || undefined,
    status: status !== "all" ? (status as LostReportStatus) : undefined,
    category: category !== "all" ? (category as LostReportCategory) : undefined,
    limit: 50,
  });

  return (
    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl md:text-3xl font-bold text-foreground">紛失届一覧</h1>
          <p className="text-muted-foreground mt-1 text-sm md:text-base">
            お客様から受け付けた落とし物の問い合わせを管理します。
          </p>
        </div>
        <Link href="/lost-reports/new">
          <Button className="rounded-full shadow-sm hover-elevate gap-2 hidden md:flex">
            <Plus className="w-4 h-4" />
            新規登録
          </Button>
        </Link>
      </div>

      <Card className="p-4 border-border/50 shadow-sm bg-card">
        <div className="flex flex-col md:flex-row gap-4">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
            <Input 
              placeholder="お名前、特徴で検索..." 
              className="pl-9 bg-background/50"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </div>
          <div className="flex gap-2">
            <Select value={status} onValueChange={setStatus}>
              <SelectTrigger className="w-[140px] bg-background/50">
                <Filter className="w-4 h-4 mr-2 text-muted-foreground" />
                <SelectValue placeholder="ステータス" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">すべて</SelectItem>
                <SelectItem value="探索中">探索中</SelectItem>
                <SelectItem value="解決済">解決済</SelectItem>
                <SelectItem value="キャンセル">キャンセル</SelectItem>
              </SelectContent>
            </Select>
            <Select value={category} onValueChange={setCategory}>
              <SelectTrigger className="w-[160px] bg-background/50">
                <SelectValue placeholder="カテゴリ" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">すべてのカテゴリ</SelectItem>
                {Object.values(LostReportCategory).map(cat => (
                  <SelectItem key={cat} value={cat}>{cat}</SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </div>
      </Card>

      {isLoading ? (
        <div className="space-y-3">
          {[1, 2, 3, 4].map(i => (
            <Skeleton key={i} className="w-full h-[120px] rounded-xl" />
          ))}
        </div>
      ) : data?.items.length === 0 ? (
        <div className="text-center py-20 bg-card rounded-xl border border-border/50 border-dashed">
          <SearchX className="w-12 h-12 text-muted-foreground/30 mx-auto mb-4" />
          <p className="text-muted-foreground">条件に一致する紛失届が見つかりません</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-4">
          {data?.items.map((item) => (
            <Link key={item.id} href={`/lost-reports/${item.id}`}>
              <Card className="p-5 hover:bg-secondary/20 transition-colors cursor-pointer border-border/50 shadow-sm hover-elevate group">
                <div className="flex flex-col md:flex-row gap-4 md:items-start justify-between">
                  <div className="space-y-2 flex-1">
                    <div className="flex items-center gap-2 flex-wrap">
                      <Badge variant="outline" className={`text-[10px] py-0 px-2 ${getStatusColor(item.status)}`}>
                        {item.status}
                      </Badge>
                      <Badge variant="outline" className={`text-[10px] py-0 px-2 ${getCategoryColor(item.category)}`}>
                        {item.category}
                      </Badge>
                      <span className="text-xs text-muted-foreground">受付: {formatDate(item.createdAt)}</span>
                    </div>
                    
                    <p className="font-medium text-foreground text-lg group-hover:text-primary transition-colors">
                      {item.features || "特徴の記載なし"}
                    </p>
                    
                    <div className="flex flex-col sm:flex-row gap-3 text-sm text-muted-foreground mt-2">
                      <div className="flex items-center gap-1.5">
                        <CalendarIcon className="w-4 h-4 shrink-0" />
                        <span className="truncate">
                          紛失日時: {item.lostDatetimeFrom ? formatDate(item.lostDatetimeFrom) : "不明"}頃
                        </span>
                      </div>
                      <div className="flex items-center gap-1.5">
                        <User className="w-4 h-4 shrink-0" />
                        <div className="flex items-center gap-2">
                          <MaskedText text={item.ownerName} />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </Card>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}