import React from "react";
import { useLocation } from "wouter";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { Loader2, ArrowLeft } from "lucide-react";
import { useCreateLostReport, LostReportInputCategory } from "@workspace/api-client-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Card, CardContent } from "@/components/ui/card";
import { useToast } from "@/hooks/use-toast";

const formSchema = z.object({
  ownerName: z.string().min(1, "氏名を入力してください"),
  ownerContact: z.string().min(1, "連絡先を入力してください"),
  category: z.enum([
    "財布・カバン類", "衣類", "電子機器", "傘", "その他"
  ] as const),
  features: z.string().min(1, "特徴を入力してください"),
  lostDatetimeFrom: z.string().optional(),
  lostDatetimeTo: z.string().optional(),
  lostLocationEstimated: z.string().optional(),
});

type FormValues = z.infer<typeof formSchema>;

export default function NewLostReport() {
  const [, setLocation] = useLocation();
  const { toast } = useToast();
  const createReport = useCreateLostReport();

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      ownerName: "",
      ownerContact: "",
      category: "財布・カバン類",
      features: "",
      lostDatetimeFrom: "",
      lostDatetimeTo: "",
      lostLocationEstimated: "",
    }
  });

  const onSubmit = async (values: FormValues) => {
    try {
      const result = await createReport.mutateAsync({
        data: {
          ownerName: values.ownerName,
          ownerContact: values.ownerContact,
          category: values.category,
          features: values.features,
          lostDatetimeFrom: values.lostDatetimeFrom ? new Date(values.lostDatetimeFrom).toISOString() : undefined,
          lostDatetimeTo: values.lostDatetimeTo ? new Date(values.lostDatetimeTo).toISOString() : undefined,
          lostLocationEstimated: values.lostLocationEstimated || undefined,
        }
      });
      
      toast({ title: "紛失届を登録しました" });
      setLocation(`/lost-reports/${result.report.id}`);
    } catch (error) {
      toast({ title: "登録に失敗しました", variant: "destructive" });
    }
  };

  return (
    <div className="max-w-2xl mx-auto space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" onClick={() => setLocation("/lost-reports")}>
          <ArrowLeft className="w-5 h-5" />
        </Button>
        <div>
          <h1 className="text-2xl font-bold">紛失届の登録</h1>
          <p className="text-sm text-muted-foreground">お客様からの問い合わせ内容を記録します。</p>
        </div>
      </div>

      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-8">
          <Card className="shadow-sm border-border/50">
            <CardContent className="p-6 space-y-6">
              <h3 className="font-bold border-b pb-2 text-primary">お客様情報</h3>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <FormField
                  control={form.control}
                  name="ownerName"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>お名前 <span className="text-destructive">*</span></FormLabel>
                      <FormControl>
                        <Input placeholder="例: 山田 太郎" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="ownerContact"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>連絡先（電話またはメール） <span className="text-destructive">*</span></FormLabel>
                      <FormControl>
                        <Input placeholder="090-XXXX-XXXX" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>
            </CardContent>
          </Card>

          <Card className="shadow-sm border-border/50">
            <CardContent className="p-6 space-y-6">
              <h3 className="font-bold border-b pb-2 text-primary">紛失物の情報</h3>

              <FormField
                control={form.control}
                name="category"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>カテゴリ <span className="text-destructive">*</span></FormLabel>
                    <Select onValueChange={field.onChange} defaultValue={field.value}>
                      <FormControl>
                        <SelectTrigger>
                          <SelectValue placeholder="カテゴリを選択" />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        {Object.values(LostReportInputCategory).map(cat => (
                          <SelectItem key={cat} value={cat}>{cat}</SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="features"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>特徴 <span className="text-destructive">*</span></FormLabel>
                    <FormControl>
                      <Textarea placeholder="色、ブランド、中身の特徴など" className="resize-none min-h-[100px]" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <FormField
                  control={form.control}
                  name="lostDatetimeFrom"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>紛失日時（いつから）</FormLabel>
                      <FormControl>
                        <Input type="datetime-local" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="lostDatetimeTo"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>紛失日時（いつまで）</FormLabel>
                      <FormControl>
                        <Input type="datetime-local" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>

              <FormField
                control={form.control}
                name="lostLocationEstimated"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>紛失したと思われる場所</FormLabel>
                    <FormControl>
                      <Input placeholder="例: フードコート、または南口トイレ付近" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </CardContent>
          </Card>

          <div className="flex justify-end gap-4">
            <Button type="button" variant="outline" onClick={() => setLocation("/lost-reports")}>
              キャンセル
            </Button>
            <Button type="submit" disabled={createReport.isPending} className="px-8 shadow-sm">
              {createReport.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              登録して類似検索
            </Button>
          </div>
        </form>
      </Form>
    </div>
  );
}