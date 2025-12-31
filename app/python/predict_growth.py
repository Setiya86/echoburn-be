import sys
import json
import pandas as pd
from prophet import Prophet
import logging

# Setup logging dasar untuk debug jika ada error
logging.basicConfig(level=logging.INFO)

try:
    # 1. Baca Input dari Argument (File JSON sementara dari Laravel)
    input_file_path = sys.argv[1]
    with open(input_file_path, 'r') as f:
        data_raw = json.load(f)

    # 2. Validasi Data
    if len(data_raw) < 2:
        # Prophet butuh minimal 2 titik data. Kita return error json.
        print(json.dumps({"error": "Data historis terlalu sedikit (min 2 bulan)"}))
        sys.exit(0)

    # 3. Persiapan DataFrame
    df = pd.DataFrame(data_raw)
    # Prophet mewajibkan nama kolom 'ds' (date) dan 'y' (value)
    df = df.rename(columns={'date': 'ds', 'total': 'y'})
    df['ds'] = pd.to_datetime(df['ds'])

    # 4. Konfigurasi Model Prophet
    # growth='linear' cocok untuk tahap awal. 
    # Jika pasar sudah jenuh, nanti ganti 'logistic'.
    m = Prophet(
        growth='linear',
        yearly_seasonality=False, # Matikan jika data < 1 tahun
        weekly_seasonality=False, 
        daily_seasonality=False,
        seasonality_mode='additive' 
    )
    
    # Menambahkan seasonality bulanan secara manual (karena defaultnya mati)
    # Agar bisa membaca pola naik/turun bulanan
    m.add_seasonality(name='monthly', period=30.5, fourier_order=5)

    m.fit(df)

    # 5. Buat DataFrame Masa Depan (6 Bulan ke depan)
    future = m.make_future_dataframe(periods=6, freq='M') 
    
    # 6. Prediksi
    forecast = m.predict(future)

    # 7. Cleaning Hasil
    # Ambil kolom penting saja: tanggal, prediksi, batas bawah, batas atas
    result_df = forecast[['ds', 'yhat', 'yhat_lower', 'yhat_upper']].tail(6) # Ambil 6 bulan prediksi saja
    
    # Pastikan tidak ada nilai negatif (Floor at 0)
    result_df['yhat'] = result_df['yhat'].apply(lambda x: max(0, int(x)))
    result_df['yhat_lower'] = result_df['yhat_lower'].apply(lambda x: max(0, int(x)))
    result_df['yhat_upper'] = result_df['yhat_upper'].apply(lambda x: max(0, int(x)))
    
    # Format tanggal ke String YYYY-MM-DD
    result_df['ds'] = result_df['ds'].dt.strftime('%Y-%m-%d')

    # 8. Output JSON ke Laravel
    print(result_df.to_json(orient='records'))

except Exception as e:
    # Tangkap error Python dan kirim sebagai JSON
    error_response = {"error": str(e)}
    print(json.dumps(error_response))
    sys.exit(1)