import React from "react";
import { Link } from "wouter";
import { 
  PackageSearch, 
  AlertCircle, 
  CalendarDays, 
  CheckCircle2, 
  PackageOpen, 
  Search 
} from "lucide-react";
import { useGetDashboardStats, useGetDashboardWeeklyTrend } from "@workspace/api-client-react";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { Button } from "@/components/ui/button";
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend } from "recharts";

export default function Dashboard() {
  const { data: stats, isLoading: statsLoading } = useGetDashboardStats();
  const { data: trend, isLoading: trendLoading } = useGetDashboardWeeklyTrend();

  return (
    <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
      <div>
        <h1 className="text-3xl font-bold text-foreground">ダッシュボード</h1>
        <p className="text-muted-foreground mt-2">
          現在の落とし物状況と直近の傾向を確認できます。
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          title="現在保管中"
          value={stats?.storing}
          loading={statsLoading}
          icon={PackageSearch}
          colorClass="text-blue-600 bg-blue-100"
          description="現在管理センターにある数"
        />
        <StatCard
          title="本日拾得"
          value={stats?.todayFound}
          loading={statsLoading}
          icon={CalendarDays}
          colorClass="text-amber-600 bg-amber-100"
          description="今日届けられた落とし物"
        />
        <StatCard
          title="期限間近"
          value={stats?.nearExpiry}
          loading={statsLoading}
          icon={AlertCircle}
          colorClass="text-red-600 bg-red-100"
          description="保管期限(3ヶ月)が近いもの"
        />
        <StatCard
          title="今月の返還"
          value={stats?.returnedThisMonth}
          loading={statsLoading}
          icon={CheckCircle2}
          colorClass="text-green-600 bg-green-100"
          description="持ち主に返却された数"
        />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Card className="lg:col-span-2 shadow-sm border-border/50">
          <CardHeader>
            <CardTitle>直近7日間の傾向</CardTitle>
            <CardDescription>拾得数と返還数の推移</CardDescription>
          </CardHeader>
          <CardContent>
            {trendLoading ? (
              <Skeleton className="w-full h-[300px] rounded-xl" />
            ) : (
              <div className="h-[300px] w-full">
                <ResponsiveContainer width="100%" height="100%">
                  <LineChart data={trend} margin={{ top: 5, right: 20, bottom: 5, left: 0 }}>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="hsl(var(--border))" />
                    <XAxis 
                      dataKey="date" 
                      axisLine={false}
                      tickLine={false}
                      tick={{ fill: 'hsl(var(--muted-foreground))', fontSize: 12 }}
                      tickFormatter={(val) => {
                        const d = new Date(val);
                        return `${d.getMonth() + 1}/${d.getDate()}`;
                      }}
                    />
                    <YAxis 
                      axisLine={false}
                      tickLine={false}
                      tick={{ fill: 'hsl(var(--muted-foreground))', fontSize: 12 }}
                    />
                    <Tooltip 
                      contentStyle={{ borderRadius: '12px', border: 'none', boxShadow: '0 4px 12px rgba(0,0,0,0.1)' }}
                      labelFormatter={(val) => new Date(val).toLocaleDateString('ja-JP')}
                    />
                    <Legend iconType="circle" wrapperStyle={{ fontSize: 14 }} />
                    <Line 
                      name="拾得物" 
                      type="monotone" 
                      dataKey="found" 
                      stroke="hsl(var(--primary))" 
                      strokeWidth={3}
                      dot={{ r: 4, strokeWidth: 2 }}
                      activeDot={{ r: 6 }}
                    />
                    <Line 
                      name="返還数" 
                      type="monotone" 
                      dataKey="returned" 
                      stroke="hsl(180, 30%, 50%)" 
                      strokeWidth={3}
                      dot={{ r: 4, strokeWidth: 2 }}
                      activeDot={{ r: 6 }}
                    />
                  </LineChart>
                </ResponsiveContainer>
              </div>
            )}
          </CardContent>
        </Card>

        <div className="space-y-6">
          <Card className="shadow-sm border-border/50 bg-secondary/30">
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Search className="w-5 h-5 text-primary" />
                クイックアクション
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <Link href="/found-items/new">
                <Button className="w-full justify-start text-left bg-card hover:bg-secondary text-foreground border border-border shadow-sm hover-elevate h-14 rounded-xl" variant="outline">
                  <div className="flex items-center gap-3 w-full">
                    <div className="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center">
                      <PackageOpen className="w-4 h-4" />
                    </div>
                    <div>
                      <div className="font-semibold text-sm">拾得物を登録</div>
                      <div className="text-xs text-muted-foreground font-normal">新しい落とし物をシステムに追加</div>
                    </div>
                  </div>
                </Button>
              </Link>
              
              <Link href="/lost-reports/new">
                <Button className="w-full justify-start text-left bg-card hover:bg-secondary text-foreground border border-border shadow-sm hover-elevate h-14 rounded-xl" variant="outline">
                  <div className="flex items-center gap-3 w-full">
                    <div className="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center">
                      <Search className="w-4 h-4" />
                    </div>
                    <div>
                      <div className="font-semibold text-sm">紛失届を作成</div>
                      <div className="text-xs text-muted-foreground font-normal">お客様からの問い合わせを記録</div>
                    </div>
                  </div>
                </Button>
              </Link>
            </CardContent>
          </Card>

          <Card className="shadow-sm border-border/50">
            <CardHeader className="pb-3">
              <CardTitle>要対応タスク</CardTitle>
            </CardHeader>
            <CardContent>
              {statsLoading ? (
                <div className="space-y-3">
                  <Skeleton className="h-12 w-full rounded-lg" />
                  <Skeleton className="h-12 w-full rounded-lg" />
                </div>
              ) : (
                <div className="space-y-3">
                  <div className="flex items-center justify-between p-3 rounded-lg border border-border/60 bg-card">
                    <div className="flex items-center gap-3">
                      <div className="w-2 h-2 rounded-full bg-amber-500" />
                      <span className="text-sm font-medium">未確認のマッチング</span>
                    </div>
                    <span className="font-bold text-lg">{stats?.pendingMatches || 0}</span>
                  </div>
                  <div className="flex items-center justify-between p-3 rounded-lg border border-border/60 bg-card">
                    <div className="flex items-center gap-3">
                      <div className="w-2 h-2 rounded-full bg-blue-500" />
                      <span className="text-sm font-medium">探索中の紛失届</span>
                    </div>
                    <span className="font-bold text-lg">{stats?.activeLostReports || 0}</span>
                  </div>
                </div>
              )}
              <div className="mt-4">
                <Link href="/matches">
                  <Button variant="link" className="w-full text-primary h-auto py-2">
                    マッチング確認へ進む
                  </Button>
                </Link>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}

function StatCard({ 
  title, 
  value, 
  loading, 
  icon: Icon,
  colorClass,
  description
}: { 
  title: string; 
  value?: number; 
  loading: boolean;
  icon: any;
  colorClass: string;
  description: string;
}) {
  return (
    <Card className="shadow-sm border-border/50 overflow-hidden hover-elevate transition-all">
      <CardContent className="p-6">
        <div className="flex items-start justify-between">
          <div>
            <p className="text-sm font-medium text-muted-foreground">{title}</p>
            {loading ? (
              <Skeleton className="h-10 w-16 mt-2" />
            ) : (
              <p className="text-3xl font-bold mt-1 text-foreground">
                {value ?? 0}
              </p>
            )}
          </div>
          <div className={`p-3 rounded-2xl ${colorClass}`}>
            <Icon className="w-6 h-6" />
          </div>
        </div>
        <p className="text-xs text-muted-foreground mt-4">{description}</p>
      </CardContent>
    </Card>
  );
}