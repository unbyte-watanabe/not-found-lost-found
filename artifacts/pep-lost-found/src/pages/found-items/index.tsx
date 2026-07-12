import React, { useState } from "react";
import { Link } from "wouter";
import { Plus, Search, Filter, Calendar as CalendarIcon, PackageSearch } from "lucide-react";
import { useListFoundItems, FoundItemStatus, FoundItemCategory } from "@workspace/api-client-react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import { Card } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { formatDateTime, getStatusColor, getCategoryColor } from "@/lib/format";
import { useDebounce } from "@/hooks/use-debounce";

export default function FoundItemsList() {
  const [search, setSearch] = useState("");
  const debouncedSearch = useDebounce(search, 500);
  const [status, setStatus] = useState<string>("all");
  const [category, setCategory] = useState<string>("all");

  const { data, isLoading } = useListFoundItems({
    search: debouncedSearch || undefined,
    status: status !== "all" ? (status as FoundItemStatus) : undefined,
    category: category !== "all" ? (category as FoundItemCategory) : undefined,
    limit: 50,
  });

  return (
    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl md:text-3xl font-bold text-foreground">拾得物一覧</h1>
          <p className="text-muted-foreground mt-1 text-sm md:text-base">
            施設内で拾得された落とし物の管理を行います。
          </p>
        </div>
        <Link href="/found-items/new">
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
              placeholder="管理番号、特徴、拾得場所で検索..." 
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
                <SelectItem value="保管中">保管中</SelectItem>
                <SelectItem value="返還済">返還済</SelectItem>
                <SelectItem value="警察提出済">警察提出済</SelectItem>
                <SelectItem value="期間満了処分">期間満了処分</SelectItem>
              </SelectContent>
            </Select>
            <Select value={category} onValueChange={setCategory}>
              <SelectTrigger className="w-[160px] bg-background/50">
                <SelectValue placeholder="カテゴリ" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">すべてのカテゴリ</SelectItem>
                {Object.values(FoundItemCategory).map(cat => (
                  <SelectItem key={cat} value={cat}>{cat}</SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </div>
      </Card>

      {isLoading ? (
        <div className="space-y-3">
          {[1, 2, 3, 4, 5].map(i => (
            <Skeleton key={i} className="w-full h-24 rounded-xl" />
          ))}
        </div>
      ) : data?.items.length === 0 ? (
        <div className="text-center py-20 bg-card rounded-xl border border-border/50 border-dashed">
          <PackageSearch className="w-12 h-12 text-muted-foreground/30 mx-auto mb-4" />
          <p className="text-muted-foreground">条件に一致する拾得物が見つかりません</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-3">
          {data?.items.map((item) => (
            <Link key={item.id} href={`/found-items/${item.id}`}>
              <Card className="p-4 hover:bg-secondary/20 transition-colors cursor-pointer border-border/50 shadow-sm hover-elevate flex flex-col md:flex-row md:items-center gap-4 group">
                <div className="flex items-start gap-4 flex-1">
                  <div className="w-16 h-16 rounded-lg bg-secondary/50 border border-border overflow-hidden shrink-0 flex items-center justify-center">
                    {item.imageUrl ? (
                      <img src={item.imageUrl} alt="拾得物" className="w-full h-full object-cover" />
                    ) : (
                      <PackageSearch className="w-6 h-6 text-muted-foreground/50" />
                    )}
                  </div>
                  <div className="space-y-1.5 flex-1 min-w-0">
                    <div className="flex items-center gap-2 flex-wrap">
                      <span className="font-mono text-xs text-muted-foreground">{item.managementNo}</span>
                      <Badge variant="outline" className={`text-[10px] py-0 px-2 ${getStatusColor(item.status)}`}>
                        {item.status}
                      </Badge>
                      <Badge variant="outline" className={`text-[10px] py-0 px-2 ${getCategoryColor(item.category)}`}>
                        {item.category}
                      </Badge>
                    </div>
                    <p className="font-medium text-foreground truncate group-hover:text-primary transition-colors">
                      {item.features || "特徴の記載なし"}
                    </p>
                    <div className="flex items-center gap-4 text-xs text-muted-foreground">
                      <div className="flex items-center gap-1">
                        <CalendarIcon className="w-3.5 h-3.5" />
                        {formatDateTime(item.foundDatetime)}
                      </div>
                      {item.foundLocation && (
                        <div className="truncate max-w-[150px]">
                          📍 {item.foundLocation}
                        </div>
                      )}
                    </div>
                  </div>
                </div>
                {item.status === "保管中" && (
                  <div className="shrink-0 flex md:flex-col items-center md:items-end justify-between border-t md:border-t-0 border-border/50 pt-3 md:pt-0 mt-2 md:mt-0">
                    <div className="text-xs text-muted-foreground">保管場所</div>
                    <div className="font-medium text-sm">{item.storageLocation || "未設定"}</div>
                  </div>
                )}
              </Card>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}